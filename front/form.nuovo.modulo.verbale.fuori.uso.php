<?php
/**
 * ---------------------------------------------------------------------
 * Formcreator is a plugin which allows creation of custom forms of
 * easy access.
 * ---------------------------------------------------------------------
 * LICENSE
 *
 * This file is part of Formcreator.
 *
 * Formcreator is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Formcreator is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Formcreator. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 * @copyright Copyright © 2011 - 2021 Teclib'
 * @license   http://www.gnu.org/licenses/gpl.txt GPLv3+
 * @link      https://github.com/pluginsGLPI/formcreator/
 * @link      https://pluginsglpi.github.io/formcreator/
 * @link      http://plugins.glpi-project.org/#/plugin/formcreator
 * ---------------------------------------------------------------------
 */

require_once ('../../../inc/includes.php');

// Check if current user have config right
Session::checkRight("entity", UPDATE);

// Check if plugin is activated...
if (!(new Plugin())->isActivated('formcreator')) {
   Html::displayNotFoundError();
}

if (PluginFormcreatorForm::canCreate()) {
   Html::header(
      __('Form Creator', 'formcreator'),
      $_SERVER['PHP_SELF'],
      'admin',
      'PluginFormcreatorForm'
   );

   //Search::show('PluginFormcreatorForm');

/////////////////////////////////////////////////////////////////////////////////////////////////////////////
   $out_test = fopen("/tmp/NICOLAAAAAAAAAAA.txt", "a");
   //$out_test = null; //mettere $out_test a null per non far scrivere l'output sul file di log
   fwrite($out_test, "Codice per creare il modulo verbale fuori uso in automatico.....\n\n");

   $NUMERO_SEZIONI_APPARECCHIATURA = 10;
   
   // instanciate classes
   $form           = new \PluginFormcreatorForm;
   $form_section   = new \PluginFormcreatorSection;
   $form_question  = new \PluginFormcreatorQuestion;
   
   // create form
   $form_id = $form->add([
      'name'                => "Verbale Fuori Uso",
      'is_active'           => true
      //'validation_required' => \PluginFormcreatorForm_Validator::VALIDATION_USER
   ]);
   
   $section_verbale_id = $form_section->add([
      'name'                        => "Verbale fuori uso",
      'plugin_formcreator_forms_id' => $form_id
   ]);
   
   # Tengo traccia delle questions Marca, perche' mi serve la Marca della sezione precedente
   # per la condizione di visualizzazione della sezione successiva
   $qids_marca = [];
   
   $campi_apparecchiatura = [
      ['nome' => 'Marca',          'descrizione' => 'marca apparecchiatura da dismettere'],
      ['nome' => 'Modello',        'descrizione' => 'modello apparecchiatura da dismettere'],
      ['nome' => 'Matricola',      'descrizione' => 'matricola apparecchiatura da dismettere'],
      ['nome' => 'Inventario ASL', 'descrizione' => 'n. inventario apparecchiatura da dismettere']
   ];
   
   for($i=1; $i <= $NUMERO_SEZIONI_APPARECCHIATURA; $i++) {
      # La prima la visualizzo sempre, le altre in base alla condizione
      $show_rule = $i==1?\PluginFormcreatorCondition::SHOW_RULE_ALWAYS:\PluginFormcreatorCondition::SHOW_RULE_HIDDEN;
      
      $section_apparecchiatura_id = $form_section->add([
         'name'                        => "Apparecchiatura $i",
         'plugin_formcreator_forms_id' => $form_id,
         'show_rule' => $show_rule
      ]);
   
      fwrite($out_test, "Section ID Apparecchiatura n. $i ::: $section_apparecchiatura_id\n");
      
      foreach ($campi_apparecchiatura as $campo_apparecchiatura) {
         
         $question_id = $form_question->add([
            'name'                           => $campo_apparecchiatura['nome'],
            'fieldtype'                      => 'text',
            'plugin_formcreator_sections_id' => $section_apparecchiatura_id,
            'required' => 0,
            'show_empty' => 0,
            'default_values'=>'',
            'description'=> $campo_apparecchiatura['descrizione'],
            # senza _parameters mi da errore nel creare la domanda e non me la crea
            '_parameters'     => 
               ['text' => [ 
                  'range' => [ 'range_min' => '', 'range_max' => '' ],
                  'regex' => [ 'regex' => '' ]
               ]]
         ]);
         

         if ($campo_apparecchiatura['nome'] == 'Marca') {
            $qids_marca[] = $question_id;
         }
      } #end for campi

      if ($i>1) {
         // Per le sezioni Apparecchiatura dopo la 1 metto la condizione in modo tale che le
         // visualizzo solo se il campo Marca della sezione precedente e' valorizzato
         // Va aggiunta la 'show_rule' => \PluginFormcreatorCondition::SHOW_RULE_HIDDEN alla sezione (vedi sopra)
         // altrimenti la regola non si vede
         
         // Mi recupero l'id della question Marca della sezione precedente
         $question_id_marca_sezione_precedente = $qids_marca[$i-2]; # -1 per la sezione precedente -1 perche' le sezioni partono da 1 e i qids da 0
         $condition        = new PluginFormcreatorCondition();
         $condition->add([
            'itemtype'                        => \PluginFormcreatorSection::class, //$section_apparecchiatura_obj->getType()
            'items_id'                        => $section_apparecchiatura_id, //vedere se vuole id o uuid
            'plugin_formcreator_questions_id' => $question_id_marca_sezione_precedente,
            'show_condition'                  => \PluginFormcreatorCondition::SHOW_CONDITION_NE,
            'show_value'                      => "",
            'show_logic'                      => \PluginFormcreatorCondition::SHOW_LOGIC_AND,
            'order'                           => 1,
         ]);
         
         // Log dei parametri
         fwrite($out_test, "QIDs Marca salvati (per condizione): " . implode(', ', $qids_marca) . "\n");
         fwrite($out_test, "itemtype: " . \PluginFormcreatorSection::class . "\n");
         fwrite($out_test, "items_id: $section_apparecchiatura_id\n");
         fwrite($out_test, "plugin_formcreator_questions_id: " . $question_id_marca_sezione_precedente . "\n");
      }
      
   } #end for apparecchiatura

   fwrite($out_test, "FORMID: $form_id\n");
   fclose($out_test);
/////////////////////////////////////////////////////////////////////////////////////////////////////////////

   echo '<div style="color:red;text-align:center;font-size:150%">Modulo creato con ' . $NUMERO_SEZIONI_APPARECCHIATURA . ' sezioni apparecchiatura</div>';
   
   Session::addMessageAfterRedirect("Modulo Verbale fuori uso creato con $NUMERO_SEZIONI_APPARECCHIATURA sezioni apparecchiatura");

   Html::footer();
} else {
   Html::displayRightError();
}
