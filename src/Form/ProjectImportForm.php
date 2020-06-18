<?php
namespace Scripto\Form;

use Laminas\Form\Form;

class ProjectImportForm extends Form
{
    public function init()
    {
        $this->add([
            'name' => 'submit',
            'type' => 'submit',
            'attributes' => [
                'value' => 'Import project', // @translate
            ],
        ]);

        // Disable the submit button if the project has no property.
        $project = $this->getOption('project');
        if ($project && !$project->property()) {
            $this->get('submit')->setAttribute('disabled', true);
        }
    }
}
