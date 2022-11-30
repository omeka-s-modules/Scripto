<?php
namespace Scripto\Mediawiki;

use DateTime;
use DateTimeZone;
use Laminas\Http\Client as HttpClient;
use Laminas\Http\Request;
use Laminas\Session\Container;

/**
 * MediaWiki API client
 */
class ApiClient
{
    /**
     * The MediaWiki minimum version.
     */
    const MINIMUM_VERSION = '1.30.0';

    /**
     * @var HttpClient
     */
    protected $httpClient;

    /**
     * MediaWiki API endpoint URL
     *
     * @var string
     */
    protected $apiUrl;

    /**
     * Omeka time zone
     *
     * @var string
     */
    protected $timeZone;

    /**
     * @var Container
     */
    protected $session;

    /**
     * Cache of MediaWiki site information
     *
     * @var array
     */
    protected $siteInfo;

    /**
     * Cache of MediaWiki user information
     *
     * @var array
     */
    protected $userInfo;

    /**
     * Cache of page information
     *
     * Contains all pages queried by self::queryPages(), keyed by title.
     *
     * @var array
     */
    protected $pageCache = [];

    /**
     * Cache of revision information
     *
     * Contains all revisions queried by self::queryRevision(), keyed by title.
     *
     * @var array
     */
    protected $revisionCache = [];

    /**
     * Construct the client.
     *
     * @param HttpClient $client
     * @param string $apiUrl
     * @param string $timeZone
     */
    public function __construct(HttpClient $httpClient, $apiUrl, $timeZone)
    {
        $this->httpClient = $httpClient;
        $this->apiUrl = $apiUrl;
        $this->timeZone = $timeZone;

        // Retrieve persisted MediaWiki cookies and add them to the HTTP client.
        $this->session = new Container('ScriptoMediawiki');
        if (is_array($this->session->cookies)) {
            foreach ($this->session->cookies as $cookie) {
                $this->httpClient->addCookie($cookie);
            }
        }
    }

    /**
     * Is this page created?
     *
     * @param string|array $title A string or the result of self::queryPage()
     * @return bool
     */
    public function pageIsCreated($title)
    {
        if (is_string($title)) {
            $page = $this->queryPage($title);
        } elseif (is_array($title)) {
            $page = $title;
        } else {
            throw new Exception\InvalidArgumentException('A title must be a string or an array');
        }
        return isset($page['pageid']);
    }

    /**
     * Get this page's protection data given a protection type.
     *
     * @param string $type "create", "edit", "move"
     * @return array|null Returns null if there are no protections for the type
     */
    public function getPageProtection($title, $type)
    {
        if (is_string($title)) {
            $page = $this->queryPage($title);
        } elseif (is_array($title)) {
            $page = $title;
        } else {
            throw new Exception\InvalidArgumentException('A title must be a string or an array');
        }
        if (!is_string($type)) {
            throw new Exception\InvalidArgumentException('A protection type must be a string');
        }
        foreach ($page['protection'] as $protection) {
            if ($type === $protection['type']) {
                return $protection;
            }
        }
        return null;
    }

    /**
     * Can the user perform this action on this page?
     *
     * Find the available actions in self::queryPages() under intestactions.
     *
     * @param string|array $title A string or the result of self::queryPage()
     * @param string $action
     * @return bool
     */
    public function userCan($title, $action)
    {
        if (is_string($title)) {
            $page = $this->queryPage($title);
        } elseif (is_array($title)) {
            $page = $title;
        } else {
            throw new Exception\InvalidArgumentException('A title must be a string or an array');
        }
        return isset($page['actions'][$action])
            ? (bool) $page['actions'][$action] : false;
    }

    /**
     * Is the current user logged in?
     *
     * @return bool
     */
    public function userIsLoggedIn()
    {
        if (!isset($this->userInfo)) {
            $this->queryUserInfo();
        }
        return (bool) $this->userInfo['id'];
    }

    /**
     * Is the current user in the provided group?
     *
     * @param string $group
     * @return bool
     */
    public function userIsInGroup($group)
    {
        if (!isset($this->userInfo)) {
            $this->queryUserInfo();
        }
        return in_array($group, $this->userInfo['groups']);
    }

    /**
     * Query information about a named user.
     *
     * @param string $name User name
     * @return array
     */
    public function queryUser($name)
    {
        return $this->queryUsers([$name])[0];
    }

    /**
     * Query page revisions by a named user.
     *
     * @link https://www.mediawiki.org/wiki/API:Usercontribs
     * @param string $name User name
     * @param int $limit
     * @param string $continue
     * @return array
     */
    public function queryUserContributions($name, $limit, $continue = null)
    {
        if (!is_string($name)) {
            throw new Exception\InvalidArgumentException('A name must be a string');
        }
        if (strstr($name, '|')) {
            throw new Exception\InvalidArgumentException('A name must not contain a vertical bar');
        }
        if (!is_numeric($limit)) {
            throw new Exception\InvalidArgumentException('A limit must be numeric');
        }
        if (isset($continue) && !is_string($continue)) {
            throw new Exception\InvalidArgumentException('A continue must be a string');
        }

        $request = [
            'action' => 'query',
            'list' => 'usercontribs',
            'ucuser' => $name,
            'uclimit' => $limit,
            'ucprop' => 'ids|title|flags|timestamp|size|sizediff|parsedcomment',
        ];
        if ($continue) {
            $request['uccontinue'] = $continue;
        }
        $query = $this->request($request);
        if (isset($query['error'])) {
            throw new Exception\QueryException($query['error']['info']);
        }
        // Set timestamps to DateTime objects adjusted to Omeka's time zone.
        foreach ($query['query']['usercontribs'] as $index => $userContrib) {
            $dateTime = new DateTime($userContrib['timestamp']);
            $dateTime->setTimezone(new DateTimeZone($this->timeZone));
            $query['query']['usercontribs'][$index]['timestamp'] = $dateTime;
        }
        return $query;
    }

    /**
     * Query information about named users.
     *
     * @link https://www.mediawiki.org/wiki/API:Users
     * @param array $names User names
     * @return array
     */
    public function queryUsers(array $names)
    {
        if (count($names) !== count(array_unique($names))) {
            throw new Exception\InvalidArgumentException('Names must be unique');
        }
        foreach ($names as $name) {
            if (!is_string($name)) {
                throw new Exception\InvalidArgumentException('A name must be a string');
            }
            if (strstr($name, '|')) {
                throw new Exception\InvalidArgumentException('A name must not contain a vertical bar');
            }
        }

        $query = $this->request([
            'action' => 'query',
            'list' => 'users',
            'ususers' => implode('|', $names),
            'usprop' => 'blockinfo|groups|implicitgroups|rights|editcount|registration|emailable|gender',
        ]);
        if (isset($query['error'])) {
            throw new Exception\QueryException($query['error']['info']);
        }
        return $query['query']['users'];
    }

    /**
     * Query information about all users.
     *
     * @link https://www.mediawiki.org/wiki/API:Allusers
     * @param int $limit
     * @param string $continue
     * @return array
     */
    public function queryAllUsers($limit, $continue = null)
    {
        if (!is_numeric($limit)) {
            throw new Exception\InvalidArgumentException('A limit must be numeric');
        }
        if (isset($continue) && !is_string($continue)) {
            throw new Exception\InvalidArgumentException('A continue must be a string');
        }

        $request = [
            'action' => 'query',
            'list' => 'allusers',
            'aulimit' => $limit,
            'auprop' => 'blockinfo|groups|implicitgroups|rights|editcount|registration',
        ];
        if ($continue) {
            $request['aufrom'] = $continue;
        }
        $query = $this->request($request);
        if (isset($query['error'])) {
            throw new Exception\QueryException($query['error']['info']);
        }
        foreach ($query['query']['allusers'] as $index => $user) {
            $dateTime = new DateTime($user['registration']);
            $dateTime->setTimezone(new DateTimeZone($this->timeZone));
            $query['query']['allusers'][$index]['registration'] = $dateTime;
        }
        return $query;
    }

    /**
     * Query information about a page, including its latest revision.
     *
     * @param string $title Page title
     * @return array
     */
    public function queryPage($title)
    {
        if (!isset($this->pageCache[$title])) {
            $this->pageCache[$title] = $this->queryPages([$title])[0];
        }
        return $this->pageCache[$title];
    }

    /**
     * Query information about pages, including their latest revisions.
     *
     * @link https://www.mediawiki.org/wiki/API:Info
     * @link https://www.mediawiki.org/wiki/Manual:User_rights#List_of_permissions
     * @param array $titles Page titles
     * @return array
     */
    public function queryPages(array $titles)
    {
        if (count($titles) !== count(array_unique($titles))) {
            throw new Exception\InvalidArgumentException('Titles must be unique');
        }
        foreach ($titles as $title) {
            if (!is_string($title)) {
                throw new Exception\InvalidArgumentException('A title must be a string');
            }
            if (strstr($title, '|')) {
                throw new Exception\InvalidArgumentException('A title must not contain a vertical bar');
            }
        }

        $pages = [];
        // Query nine pages at a time. The API exhibits an unusual behavior that
        // removes properties from the result if querying more than nine pages.
        // This behavior may only happen when the result includes missing pages.
        foreach (array_chunk($titles, 9) as $titleChunk) {
            $query = $this->request([
                'action' => 'query',
                'prop' => 'info|revisions',
                'titles' => implode('|', $titleChunk),
                'inprop' => 'watched|protection|url',
                'rvprop' => 'content|ids|flags|timestamp|parsedcomment|user',
                'intestactions' => 'read|edit|createpage|createtalk|protect|rollback',
            ]);
            if (isset($query['error'])) {
                throw new Exception\QueryException($query['error']['info']);
            }

            // The ordering of the response does not necessarily correspond to
            // the ordering of the input. Here we match the original ordering.
            $normalized = [];
            if (isset($query['query']['normalized'])) {
                foreach ($query['query']['normalized'] as $value) {
                    $normalized[$value['from']] = $value['to'];
                }
            }
            foreach ($titleChunk as $title) {
                $title = (string) $title;
                $normalizedTitle = $normalized[$title] ?? $title;
                foreach ($query['query']['pages'] as  $page) {
                    if ($page['title'] === $normalizedTitle) {
                        $pages[] = $page;
                        continue;
                    }
                }
            }
        }
        // Set timestamps to DateTime objects adjusted to Omeka's time zone.
        foreach ($pages as $pageIndex => $page) {
            if (isset($page['revisions'])) {
                foreach ($page['revisions'] as $revisionIndex => $revision) {
                    $dateTime = new DateTime($revision['timestamp']);
                    $dateTime->setTimezone(new DateTimeZone($this->timeZone));
                    $pages[$pageIndex]['revisions'][$revisionIndex]['timestamp'] = $dateTime;
                }
            }
            foreach ($page['protection'] as $protectionIndex => $protection) {
                $expiry = null;
                if ('infinity' !== $protection['expiry']) {
                    $expiry = new DateTime($protection['expiry']);
                    $expiry->setTimezone(new DateTimeZone($this->timeZone));
                }
                $pages[$pageIndex]['protection'][$protectionIndex]['expiry'] = $expiry;
            }
        }
        // Cache all pages.
        foreach ($pages as $page) {
            $this->pageCache[$page['title']] = $page;
        }
        return $pages;
    }

    /**
     * Query page revisions.
     *
     * @link https://www.mediawiki.org/wiki/API:Revisions
     * @param string $title
     * @param int $limit
     * @param string $continue
     * @return array
     */
    public function queryRevisions($title, $limit, $continue = null)
    {
        if (!is_string($title)) {
            throw new Exception\InvalidArgumentException('A title must be a string');
        }
        if (strstr($title, '|')) {
            throw new Exception\InvalidArgumentException('A title must not contain a vertical bar');
        }
        if (!is_numeric($limit)) {
            throw new Exception\InvalidArgumentException('A limit must be numeric');
        }
        if (isset($continue) && !is_string($continue)) {
            throw new Exception\InvalidArgumentException('A continue must be a string');
        }

        $request = [
            'action' => 'query',
            'prop' => 'revisions',
            'titles' => $title,
            'rvlimit' => $limit,
            'rvprop' => 'ids|flags|timestamp|user|size|parsedcomment',
        ];
        if ($continue) {
            $request['rvcontinue'] = $continue;
        }
        $query = $this->request($request);
        if (isset($query['error'])) {
            throw new Exception\QueryException($query['error']['info']);
        }
        // Set timestamps to DateTime objects adjusted to Omeka's time zone.
        if (isset($query['query']['pages'][0]['revisions'])) {
            foreach ($query['query']['pages'][0]['revisions'] as $index => $revision) {
                $dateTime = new DateTime($revision['timestamp']);
                $dateTime->setTimezone(new DateTimeZone($this->timeZone));
                $query['query']['pages'][0]['revisions'][$index]['timestamp'] = $dateTime;
            }
        }
        return $query;
    }

    /**
     * Query the current user's watchlist.
     *
     * @link https://www.mediawiki.org/wiki/API:Watchlist
     * @param int $endHours How many hours ago to end listing
     * @param int $limit
     * @param string $continue
     * @return array
     */
    public function queryWatchlist($hours, $limit, $continue = null)
    {
        if (!is_numeric($hours)) {
            throw new Exception\InvalidArgumentException('Hours must be numeric');
        }
        if (!is_numeric($limit)) {
            throw new Exception\InvalidArgumentException('A limit must be numeric');
        }
        if (isset($continue) && !is_string($continue)) {
            throw new Exception\InvalidArgumentException('A continue must be a string');
        }

        $request = [
            'action' => 'query',
            'list' => 'watchlist',
            'wlend' => strtotime(sprintf('-%s hour', $hours)),
            'wllimit' => $limit,
            'wltype' => 'edit|new',
            'wlprop' => 'ids|title|flags|user|userid|parsedcomment|timestamp|sizes|loginfo',
        ];
        if ($continue) {
            $request['wlcontinue'] = $continue;
        }
        $query = $this->request($request);
        if (isset($query['error'])) {
            throw new Exception\QueryException($query['error']['info']);
        }
        // Set timestamps to DateTime objects adjusted to Omeka's time zone.
        foreach ($query['query']['watchlist'] as $index => $userContrib) {
            $dateTime = new DateTime($userContrib['timestamp']);
            $dateTime->setTimezone(new DateTimeZone($this->timeZone));
            $query['query']['watchlist'][$index]['timestamp'] = $dateTime;
        }
        return $query;
    }

    /**
     * Query a page revision.
     *
     * Includes the revision's child revision ID and latest revision ID.
     *
     * @param string $title
     * @param int $revisionId
     */
    public function queryRevision($title, $revisionId)
    {
        if (!is_string($title)) {
            throw new Exception\InvalidArgumentException('A title must be a string');
        }
        if (strstr($title, '|')) {
            throw new Exception\InvalidArgumentException('A title must not contain a vertical bar');
        }
        if (!is_numeric($revisionId)) {
            throw new Exception\InvalidArgumentException('A revision ID must be numeric');
        }

        if (!isset($this->revisionCache[$title][$revisionId])) {
            $query = $this->request([
                'action' => 'query',
                'prop' => 'revisions',
                'titles' => $title,
                'rvstartid' => $revisionId,
                'rvdir' => 'newer',
                'rvlimit' => 2,
                'rvprop' => 'ids|flags|timestamp|user|size|parsedcomment|content',
            ]);
            if (isset($query['error'])) {
                throw new Exception\QueryException($query['error']['info']);
            }
            if (!isset($query['query']['pages'][0]['revisions'])) {
                // The revision exists, but it is not a revision of this page. The
                // revision ID is likely more than the highest ID of this page.
                throw new Exception\QueryException('Invalid page revision');
            }
            $revisions = $query['query']['pages'][0]['revisions'];
            $revision = $revisions[0];
            if ($revision['revid'] != $revisionId) {
                // The revision exists, but it is not a revision of this page. The
                // revision ID is likely less than the lowest ID of this page.
                throw new Exception\QueryException('Invalid page revision');
            }

            // Set the child revision ID.
            $revision['childid'] = isset($revisions[1])
                ? $query['query']['pages'][0]['revisions'][1]['revid'] : null;

            // Set the latest revision ID.
            $page = $this->queryPage($title);
            $revision['latestid'] = $page['lastrevid'];

            // Set timestamp to DateTime object adjusted to Omeka's time zone.
            $dateTime = new DateTime($revision['timestamp']);
            $dateTime->setTimezone(new DateTimeZone($this->timeZone));
            $revision['timestamp'] = $dateTime;

            $this->revisionCache[$title][$revisionId] = $revision;
        }

        return $this->revisionCache[$title][$revisionId];
    }

    /**
     * Parse revision wikitext into HTML.
     *
     * @link https://www.mediawiki.org/wiki/API:Parsing_wikitext
     * @param int $revisionId
     * @return string
     */
    public function parseRevision($revisionId)
    {
        if (!is_numeric($revisionId)) {
            throw new Exception\InvalidArgumentException('A revision ID must be numeric');
        }
        $parse = $this->request([
            'action' => 'parse',
            'oldid' => $revisionId,
            'prop' => 'text',
            'disablelimitreport' => true,
            'disableeditsection' => true,
            'disabletoc' => true,
            'wrapoutputclass' => '', // do not wrap output with div
        ]);
        if (isset($parse['error'])) {
            throw new Exception\ParseException($parse['error']['info']);
        }
        return $parse['parse']['text'];
    }

    /**
     * Edit or create a page.
     *
     * @link https://www.mediawiki.org/wiki/API:Edit
     * @param string $title
     * @param string $text
     * @param string|null $summary
     * @return array The successful edit result
     */
    public function editPage($title, $text, $summary = null)
    {
        if (!is_string($title)) {
            throw new Exception\InvalidArgumentException('Page title must be a string');
        }
        if (!is_string($text)) {
            throw new Exception\InvalidArgumentException('Page text must be a string');
        }
        if (isset($summary) && !is_string($summary)) {
            throw new Exception\InvalidArgumentException('Edit summary must be a string');
        }
        $query = $this->request([
            'action' => 'query',
            'meta' => 'tokens',
            'type' => 'csrf',
        ]);
        $edit = $this->request([
            'action' => 'edit',
            'title' => $title,
            'text' => $text,
            'summary' => $summary,
            'watchlist' => 'nochange', // watchlist not affected by edit
            'token' => $query['query']['tokens']['csrftoken'],
        ]);
        if (isset($edit['error'])) {
            throw new Exception\EditException($edit['error']['info']);
        }
        return $edit['edit'];
    }

    /**
     * Watch or unwatch pages.
     *
     * @link https://www.mediawiki.org/wiki/API:Watch
     * @param array $titles
     * @param bool $watch Set to true to watch, false to unwatch
     * @return array
     */
    public function watchAction(array $titles, $watch)
    {
        if (count($titles) !== count(array_unique($titles))) {
            throw new Exception\InvalidArgumentException('Titles must be unique');
        }
        foreach ($titles as $title) {
            if (!is_string($title)) {
                throw new Exception\InvalidArgumentException('A title must be a string');
            }
            if (strstr($title, '|')) {
                throw new Exception\InvalidArgumentException('A title must not contain a vertical bar');
            }
        }
        $query = $this->request([
            'action' => 'query',
            'meta' => 'tokens',
            'type' => 'watch',
        ]);

        $allWatch = [];
        // The API limits titles to 50 per query.
        foreach (array_chunk($titles, 50) as $titleChunk) {
            $request = [
                'action' => 'watch',
                'titles' => implode('|', $titleChunk),
                'token' => $query['query']['tokens']['watchtoken'],
            ];
            if (!$watch) {
                $request['unwatch'] = true;
            }
            $watch = $this->request($request);
            if (isset($watch['error'])) {
                throw new Exception\WatchException($watch['error']['info']);
            }
            $allWatch = array_merge($allWatch, $watch['watch']);
        }
        return $allWatch;
    }

    /**
     * Watch pages.
     *
     * @param array $titles
     * @return array
     */
    public function watchPages(array $titles)
    {
        return $this->watchAction($titles, true);
    }

    /**
     * Unwatch pages.
     *
     * @param array $titles
     * @return array
     */
    public function unwatchPages(array $titles)
    {
        return $this->watchAction($titles, false);
    }

    /**
     * Watch a page.
     *
     * @param string $title
     * @return array
     */
    public function watchPage($title)
    {
        return $this->watchAction([$title], true)[0];
    }

    /**
     * Unwatch a page.
     *
     * @param string $title
     * @return array
     */
    public function unwatchPage($title)
    {
        return $this->watchAction([$title], false)[0];
    }

    /**
     * Protect or unprotect pages.
     *
     * Note that the MediaWiki API does not natively provide batch protections.
     *
     * @link https://www.mediawiki.org/wiki/API:Protect
     * @param string $title
     * @param string $type "edit", "move", "create"
     * @param string $level "sysop", "autoconfirmed", "all"
     * @param string $expiry
     * @return array
     */
    public function protectPages(array $titles, $type, $level, $expiry)
    {
        if (count($titles) !== count(array_unique($titles))) {
            throw new Exception\InvalidArgumentException('Titles must be unique');
        }
        foreach ($titles as $title) {
            if (!is_string($title)) {
                throw new Exception\InvalidArgumentException('A title must be a string');
            }
            if (strstr($title, '|')) {
                throw new Exception\InvalidArgumentException('A title must not contain a vertical bar');
            }
        }
        if (!is_string($type)) {
            throw new Exception\InvalidArgumentException('Protection type must be a string');
        }
        if (!is_string($level)) {
            throw new Exception\InvalidArgumentException('Protection level must be a string');
        }
        if (!is_string($expiry)) {
            throw new Exception\InvalidArgumentException('Protection expiry must be a string');
        }
        $query = $this->request([
            'action' => 'query',
            'meta' => 'tokens',
            'type' => 'csrf',
        ]);

        $protects = [];
        foreach ($titles as $title) {
            $protect = $this->request([
                'action' => 'protect',
                'title' => $title,
                'protections' => sprintf('%s=%s', $type, $level),
                'expiry' => $expiry,
                'token' => $query['query']['tokens']['csrftoken'],
            ]);
            if (isset($protect['error'])) {
                throw new Exception\ProtectException($protect['error']['info']);
            }
            $protects[] = $protect['protect'];
        }
        return $protects;
    }

    /**
     * Protect or unprotect a page.
     *
     * @link https://www.mediawiki.org/wiki/API:Protect
     * @param string $title
     * @param string $type "edit", "move", "create"
     * @param string $level "sysop", "autoconfirmed", "all"
     * @param string $expiry
     * @return array
     */
    public function protectPage($title, $type, $level, $expiry)
    {
        return $this->protectPages([$title], $type, $level, $expiry)[0];
    }

    /**
     * Parse page wikitext into HTML.
     *
     * @link https://www.mediawiki.org/wiki/API:Parsing_wikitext
     * @param string $title
     * @return string|null The page HTML, or null if page does not exist
     */
    public function parsePage($title)
    {
        if (!is_string($title)) {
            throw new Exception\InvalidArgumentException('Page title must be a string');
        }
        $parse = $this->request([
            'action' => 'parse',
            'page' => $title,
            'prop' => 'text',
            'disablelimitreport' => true,
            'disableeditsection' => true,
            'disabletoc' => true,
            'wrapoutputclass' => '', // do not wrap output with div
        ]);
        if (isset($parse['error']) && 'missingtitle' !== $parse['error']['code']) {
            throw new Exception\ParseException($parse['error']['info']);
        }
        return $parse['parse']['text'] ?? null;
    }

    /**
     * Compare page revisions.
     *
     * @param int $fromRevId The first revision ID to compare
     * @param int $toRevId The second revision ID to compare
     * @return string
     */
    public function compareRevisions($fromRevId, $toRevId)
    {
        if (!is_numeric($fromRevId) || !is_numeric($toRevId)) {
            throw new Exception\InvalidArgumentException('Revision IDs must be numeric');
        }
        $compare = $this->request([
            'action' => 'compare',
            'fromrev' => $fromRevId,
            'torev' => $toRevId,
        ]);
        if (isset($compare['error'])) {
            throw new Exception\ParseException($compare['error']['info']);
        }
        return $compare['compare']['body'];
    }

    /**
     * Query information about the MediaWiki site.
     *
     * @link https://www.mediawiki.org/wiki/API:Siteinfo
     * @return array
     */
    public function querySiteInfo()
    {
        if (!isset($this->siteInfo)) {
            $query = $this->request([
                'action' => 'query',
                'meta' => 'siteinfo',
            ]);
            $this->siteInfo = $query['query']['general'];
        }
        return $this->siteInfo;
    }

    /**
     * Get the MediaWiki version.
     *
     * @return string
     */
    public function getVersion()
    {
        $generator = $this->querySiteInfo()['generator'];
        preg_match('/^mediawiki (\d\.\d+\.\d+)/i', $generator, $matches);
        return $matches ? $matches[1] : false;
    }

    /**
     * Query information about the current MediaWiki user.
     *
     * @link https://www.mediawiki.org/wiki/API:Userinfo
     * @return array
     */
    public function queryUserInfo()
    {
        if (!isset($this->userInfo)) {
            $query = $this->request([
                'action' => 'query',
                'meta' => 'userinfo',
                'uiprop' => 'realname|email|registrationdate|editcount|groups|implicitgroups',
            ]);
            $this->userInfo = $query['query']['userinfo'];
        }
        return $this->userInfo;
    }

    /**
     * Create a MediaWiki account using the default requests.
     *
     * @link https://www.mediawiki.org/wiki/API:Account_creation
     * @param string $username Username for authentication
     * @param string $password Password for authentication
     * @param string $retype Retype password
     * @param string $email Email address
     * @param string $realname Real name of the user
     * @return array The successful create account result
     */
    public function createAccount($username, $password, $retype, $email, $realname)
    {
        $query = $this->request([
            'action' => 'query',
            'meta' => 'tokens',
            'type' => 'createaccount',
        ]);
        $createaccount = $this->request([
            'action' => 'createaccount',
            'createreturnurl' => 'http://example.com/', // currently unused but required
            'createtoken' => $query['query']['tokens']['createaccounttoken'],
            'username' => $username,
            'password' => $password,
            'retype' => $password,
            'email' => $email,
            'realname' => $realname,
        ]);
        if (isset($createaccount['error'])) {
            throw new Exception\CreateaccountException($createaccount['error']['info']);
        }
        if ('FAIL' === $createaccount['createaccount']['status']) {
            throw new Exception\CreateaccountException($createaccount['createaccount']['message']);
        }
        return $createaccount['createaccount'];
    }

    /**
     * Log in to MediaWiki using the default requests.
     *
     * @link https://www.mediawiki.org/wiki/API:Login
     * @param string $username Username for authentication
     * @param string $password Password for authentication
     * @return array The successful login result
     */
    public function login($username, $password)
    {
        $query = $this->request([
            'action' => 'query',
            'meta' => 'tokens',
            'type' => 'login',
        ]);
        $clientlogin = $this->request([
            'action' => 'clientlogin',
            'loginreturnurl' => 'http://example.com/', // currently unused but required
            'logintoken' => $query['query']['tokens']['logintoken'],
            'username' => $username,
            'password' => $password,
            'rememberMe' => true,
        ]);
        if (isset($clientlogin['error'])) {
            throw new Exception\ClientloginException($clientlogin['error']['info']);
        }
        if ('FAIL' === $clientlogin['clientlogin']['status']) {
            throw new Exception\ClientloginException($clientlogin['clientlogin']['message']);
        }
        // Persist the authentication cookies.
        $this->session->cookies = $this->httpClient->getCookies();
        // Set user information.
        $this->userInfo = null;
        $this->userInfo = $this->queryUserInfo();
        return $clientlogin['clientlogin'];
    }

    /**
     * Log out of MediaWiki.
     *
     * @link https://www.mediawiki.org/wiki/API:Logout
     */
    public function logout()
    {
        $query = $this->request([
            'action' => 'query',
            'meta' => 'tokens',
            'type' => 'csrf',
        ]);
        $this->request([
            'action' => 'logout',
            'token' => $query['query']['tokens']['csrftoken'],
        ]);
        $this->httpClient->clearCookies(); // Clear HTTP client cookies
        $this->session->cookies = null; // Clear session cookies
        $this->userInfo = null; // Reset MediaWiki user information
        $this->userInfo = $this->queryUserInfo();
    }

    /**
     * Make a HTTP request
     *
     * Returns JSON response format version 2.
     *
     * @link https://www.mediawiki.org/wiki/API:JSON_version_2
     * @param array $params
     * @return array
     */
    public function request(array $params = [])
    {
        $params['format'] = 'json';
        $params['formatversion'] = '2';

        $request = new Request;
        $request->setUri($this->apiUrl);
        $request->setMethod(Request::METHOD_POST);
        $request->getPost()->fromArray($params);

        $response = $this->httpClient->send($request);
        if ($response->isSuccess()) {
            return json_decode($response->getBody(), true);
        }
        throw new Exception\RequestException($response->renderStatusLine());
    }
}
