<?php
namespace Scripto\Form;

use Laminas\Form\Form;

class ProjectSyncForm extends Form
{
    public function init()
    {
        $this->add([
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => [
                'value' => 'Sync project', // @translate
            ],
        ]);

        // Disable the submit button if the project has no item set.
        $project = $this->getOption('project');
        if ($project && !$project->itemSet()) {
            $this->get('submit')->setAttribute('disabled', true);
        }
    }
}
