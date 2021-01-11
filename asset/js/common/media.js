document.addEventListener('DOMContentLoaded', e => {

const panzoomContainer       = document.getElementById('panzoom-container');
const panzoomElem            = document.getElementById('panzoom');
const panzoomImg             = document.getElementById('panzoom-img');
const zoomInButton           = document.getElementById('panzoom-zoom-in');
const zoomOutButton          = document.getElementById('panzoom-zoom-out');
const rotateLeftButton       = document.getElementById('panzoom-rotate-left');
const rotateRightButton      = document.getElementById('panzoom-rotate-right');
const resetButton            = document.getElementById('panzoom-reset');
const fullscreenButton       = document.getElementById('fullscreen');
const deleteButton           = document.getElementById('delete-button');
const horizontalLayoutButton = document.getElementById('horizontal-layout');
const verticalLayoutButton   = document.getElementById('vertical-layout'); 
const body                   = document.querySelector('body');
const layoutContainer        = document.getElementById('wikitext-layout');
const wlContainer            = document.getElementById('watchlist-container');
const wlWatchedButton        = document.getElementById('watchlist-watched');
const wlNotWatchedButton     = document.getElementById('watchlist-not-watched');
const wlWatchSuccess         = document.getElementById('watch-success');
const wlUnwatchSuccess       = document.getElementById('unwatch-success');

let panzoom;
let rotateDeg;

initMediaViewer();

// Handle panzoom click to focus.
panzoomContainer.addEventListener('click', e => {
    panzoomContainer.focus();
});
// Handle panning by arrow keys.
panzoomContainer.addEventListener('keydown', e => {
    switch (e.code) {
        case 'ArrowUp':
            panzoom.pan(0, -2, {relative: true});
            break;
        case 'ArrowDown':
            panzoom.pan(0, 2, {relative: true});
            break;
        case 'ArrowLeft':
            panzoom.pan(-2, 0, {relative: true});
            break;
        case 'ArrowRight':
            panzoom.pan(2, 0, {relative: true});
            break;
        default:
            return;
    }
    e.preventDefault();
});
// Handle the scroll wheel.
panzoomContainer.addEventListener('wheel', panzoom.zoomWithWheel);
// Handle the zoom in button.
zoomInButton.addEventListener('click', panzoom.zoomIn);
// Handle the zoom out button.
zoomOutButton.addEventListener('click', panzoom.zoomOut);
// Handle the reset button.
resetButton.addEventListener('click', e => {
    panzoom.reset();
    resetRotate();
});
// Handle the rotate left button.
rotateLeftButton.addEventListener('click', e => {
    rotateDeg = rotateDeg - 90;
    panzoomImg.style.transition = 'transform 0.25s';
    panzoomImg.style.transform = `rotate(${rotateDeg}deg)`;
});
// Handle the rotate right button.
rotateRightButton.addEventListener('click', e => {
    rotateDeg = rotateDeg + 90;
    panzoomImg.style.transition = 'transform 0.25s';
    panzoomImg.style.transform = `rotate(${rotateDeg}deg)`;
});

// Handle the fullscreen (focus) button.
if (fullscreenButton) {
  fullscreenButton.addEventListener('click', e => {
      if (body.classList.contains('fullscreen')) {
          disableFullscreen();
      } else {
          enableFullscreen();
      }
  });
}
// Handle the horizontal layout button.
if (horizontalLayoutButton) {
  horizontalLayoutButton.addEventListener('click', e => {
    enableHorizontalLayout();
  });
}
// Handle the vertical layout button.
if (verticalLayoutButton) {
  verticalLayoutButton.addEventListener('click', e => {
    enableVerticalLayout();
  });
}
// Handle the watchlist watched button
wlWatchedButton.addEventListener('click', e => {
    handleWatchlistButton();
});
// Handle the watchlist not-watched button
wlNotWatchedButton.addEventListener('click', e => {
    handleWatchlistButton();
});
// Initialize the media viewer.
function initMediaViewer() {
    rotateDeg = 0
    panzoom = Panzoom(panzoomElem, {});
}
// Reset rotation.
function resetRotate() {
    rotateDeg = 0;
    panzoomImg.style.transition = 'none';
    panzoomImg.style.transform = 'none';
}
// Enable fullscreen.
function enableFullscreen() {
  body.classList.add('fullscreen');
}
// Disable fullscreen.
function disableFullscreen() {
  body.classList.remove('fullscreen');
}
// Enable horizontal layout.
function enableHorizontalLayout() {
  layoutContainer.classList.remove('vertical');
  layoutContainer.classList.add('horizontal');
  horizontalLayoutButton.setAttribute('class', 'active');
  horizontalLayoutButton.setAttribute('disabled', true);
  verticalLayoutButton.removeAttribute('class');
  verticalLayoutButton.removeAttribute('disabled');
}
// Enable vertical layout.
function enableVerticalLayout() {
  layoutContainer.classList.remove('horizontal');
  layoutContainer.classList.add('vertical');
  verticalLayoutButton.setAttribute('class', 'active');
  verticalLayoutButton.setAttribute('disabled', true);
  horizontalLayoutButton.removeAttribute('class');
  horizontalLayoutButton.removeAttribute('disabled');
}
// Handle the watchlist toggle.
function handleWatchlistButton() {
    const watching = (1 == wlContainer.dataset.watching) ? 0 : 1;
    fetch(wlContainer.dataset.url, {
        method: 'post',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `watching=${watching}`
    })
    .then(response => {
        if (watching) {
            wlNotWatchedButton.style.display = 'none';
            wlWatchedButton.style.display = 'inline-block';
            wlContainer.dataset.watching = 1;
            wlWatchSuccess.classList.add('fadeInOut');
            window.setTimeout(function() {
              wlWatchSuccess.classList.remove('fadeInOut');
            }, 3000);
        } else {
            wlNotWatchedButton.style.display = 'inline-block';
            wlWatchedButton.style.display = 'none';
            wlContainer.dataset.watching = 0;
            wlUnwatchSuccess.classList.add('fadeInOut');
            window.setTimeout(function() {
              wlUnwatchSuccess.classList.remove('fadeInOut');
            }, 3000);
        }

    });
}
});
