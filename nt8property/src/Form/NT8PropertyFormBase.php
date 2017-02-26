<?php

namespace Drupal\nt8property\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements an example form.
 */
class NT8PropertyFormBase extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'nt8property_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['path_to_fixture'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Path to Property Fixture'),
      '#default_value' => 'H610_ZZ'
    );
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Load'),
      '#button_type' => 'primary',
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $propref = $form['path_to_fixture']['#value'];



    $req_path = Url::fromRoute('property.getFixture', ['propRef' => $propref], ['absolute' => TRUE])->toString();

    $response = \Drupal::httpClient()->get($req_path, []);
    $data = json_encode($response->getBody());

    // Use the entity manager.
    $node = \Drupal::entityTypeManager()->getStorage('node')->create(array('type' => 'property', 'title' => 'Another node'));

    $node->save();
  }

}