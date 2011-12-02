<?php
namespace Codeception\ResultPrinter;

class HTML extends \Codeception\ResultPrinter
{
    /**
     * @var boolean
     */
    protected $printsHTML = TRUE;

    /**
     * @var integer
     */
    protected $id = 0;

    /**
     * @var string
     */
    protected $scenarios = '';

    /**
     * @var string
     */
    protected $templatePath;

	/**
	 * @var int
	 */
	protected $timeTaken = 0;

    /**
     * Constructor.
     *
     * @param  mixed   $out
     * @throws InvalidArgumentException
     */
    public function __construct($out = NULL)
    {
        parent::__construct($out);

        $this->templatePath = sprintf(
          '%s%stemplate%s',

          dirname(__FILE__),
          DIRECTORY_SEPARATOR,
          DIRECTORY_SEPARATOR
        );
    }

    /**
     * Handler for 'start class' event.
     *
     * @param  string $name
     */
    protected function startClass($name)
    {
    }

    /**
     * Handler for 'on test' event.
     *
     * @param  string  $name
     * @param  boolean $success
     * @param  array   $steps
     */
    protected function onTest($name, $success = TRUE, array $steps = array(), $time = 0)
    {
	    $this->timeTaken += $time;
        if ($this->testStatus == \PHPUnit_Runner_BaseTestRunner::STATUS_FAILURE) {
            $scenarioStatus = 'scenarioFailed';
        }

        else if ($this->testStatus == \PHPUnit_Runner_BaseTestRunner::STATUS_SKIPPED) {
            $scenarioStatus = 'scenarioSkipped';
        }

        else if ($this->testStatus == \PHPUnit_Runner_BaseTestRunner::STATUS_INCOMPLETE) {
            $scenarioStatus = 'scenarioIncomplete';
        }

        else {
            $scenarioStatus = 'scenarioSuccess';
        }

        $stepsBuffer  = '';

        foreach ($steps as $step) {
            $currentStepName = $step->getName();

            $stepText = $currentStepName;

            $stepTemplate = new \Text_Template(
              $this->templatePath . 'step.html'
            );

            $stepTemplate->setVar(
              array(
                'action' => $step->getHumanizedAction() . ' ' . $step->getArguments(TRUE),
              )
            );

            $stepsBuffer .= $stepTemplate->render();
        }

        $scenarioTemplate = new \Text_Template(
          $this->templatePath . 'scenario.html'
        );

        $scenarioTemplate->setVar(
          array(
            'id'             => ++$this->id,
            'name'           => $name,
            'scenarioStatus' => $scenarioStatus,
            'steps'          => $stepsBuffer,
	        'time' => round($time, 2)
          )
        );

        $this->scenarios .= $scenarioTemplate->render();
    }

    /**
     * Handler for 'end run' event.
     *
     */
    protected function endRun()
    {

        $scenarioHeaderTemplate = new \Text_Template(
          $this->templatePath . 'scenario_header.html'
        );

	    $status = !$this->failed ? '<span style="color: green">OK</span>' : '<span style="color: red">FAILED</span>';

        $scenarioHeaderTemplate->setVar(
          array(
            'name' => 'TestGuy Results',
	        'status' => $status,
	        'time' => round($this->timeTaken,1)
	      )
        );

        $header = $scenarioHeaderTemplate->render();

        $scenariosTemplate = new \Text_Template(
          $this->templatePath . 'scenarios.html'
        );

        $scenariosTemplate->setVar(
          array(
	        'header'              => $header,
            'scenarios'           => $this->scenarios,
            'successfulScenarios' => $this->successful,
            'failedScenarios'     => $this->failed,
            'skippedScenarios'    => $this->skipped,
            'incompleteScenarios' => $this->incomplete
          )
        );

        $this->write($scenariosTemplate->render());
    }


}
