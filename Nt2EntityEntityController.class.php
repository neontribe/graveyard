<?php

class nt2EntityEntityController extends EntityAPIController {
	
	//Override buildContent
	public function buildContent($entity, $view_mode = 'full', $langcode = NULL, $content = array()) {
		//Invoke parent and grab return so we can modify for output.
		$build = parent::buildContent($entity, $view_mode, $langcode, $content);

		$build['reference'] = array(
			'#type' => 'markup',
			'#markup' => check_plain($entity->reference),
			'#prefix' => '<div class="cottage-reference"><p>Reference:',
			'#suffix' => '</p></div>',
		);
		$build['brandcode'] = array(
			'#type' => 'markup',
			'#markup' => check_plain($entity->brandcode),
			'#prefix' => '<div class="cottage-brandcode"><p>Cottage Code: ',
			'#suffix' => '</p></div>',
		);
		$build['name'] = array(
			'#type' => 'markup',
			'#markup' => check_plain($entity->name),
			'#prefix' => '<div class="cottage-name"><p>Name: ',
			'#suffix' => '</p></div>',
		);

		return $build;
	}
}