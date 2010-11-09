<?php
/*
 DefaultFormRenderer
 Author: B. Dailey
 Website: www.dailytechnology.net
 Free to be used, modified, & distributed so long as this text remains.
 */
require_once "HTML/QuickForm/Renderer/Default.php";

class AuthRenderer {
	// HTML Quick Form
	var $Form;
	var $FormRenderer;
	var $FormTemplate;
	var $ElementTemplate;
	var $HeaderTemplate;
	var $RequiredNoteTemplate;

	/**
	 * Form constructor.
	 */
	function __construct(& $Form) {
		$this->Form = & $Form;

		$this->setFormTemplate();
		$this->setElementTemplate();
		$this->setHeaderTemplate();
		$this->setRequiredNoteTemplate();

		$this->FormRenderer = & new HTML_QuickForm_Renderer_Default();
		$this->FormRenderer->setFormTemplate($this->FormTemplate);
		$this->FormRenderer->setElementTemplate($this->ElementTemplate);
		$this->FormRenderer->setHeaderTemplate($this->HeaderTemplate);
		$this->FormRenderer->setRequiredNoteTemplate($this->RequiredNoteTemplate);
        $this->Form->accept($this->FormRenderer);
	}

	function setFormTemplate() {
		$this->FormTemplate= <<<HTML
            <form {attributes}>
                <table class="publist">
                    <tbody>
                        <tr>
                            <th>Authorize</th>
                            <th>Acces Level</th>
                            <th>Login</th>
                            <th>Name</th>
                            <th>Email</th>
                        </tr>
                        {content}
                    </tbody>
                </table>
            </form>
HTML;
	}

	function setElementTemplate() {
		$this->ElementTemplate = '<tr><td class="stats_odd">{element}</td></tr>';
	}

	function setHeaderTemplate() {
		$this->HeaderTemplate = '<tr><th>{header}</th></tr>';
	}

	function setRequiredNoteTemplate() {
		$this->RequiredNoteTemplate = <<<HTML
                    <div id="requirednote clear"><span class="required">*</span> Denotes required field.</div>
HTML;
	}
	
    function toHtml() {
        return $this->FormRenderer->toHtml();
    }

}
