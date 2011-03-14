<?php
/**
 * A form field that renders a set of accordions for editing metadata, then
 * saves the serialized data into a single field.
 *
 * @package silverstripe-metadata
 */
class MetadataSetField extends FormField {

	public function saveInto($record) {
		$schemas  = $this->form->getRecord()->getSchemas();
		$value    = $this->Value();
		$metadata = array();

		foreach ($schemas as $schema) {
			$metadata[$schema->Name] = array();
			$fields = $schema->getFormFields();
			$namesMap = array();

			foreach ($fields as $field) {
				$brPos = strrpos($field->Name(), '[');
				$name  = substr($field->Name(), $brPos + 1, -1);

				$namesMap[$field->Name()] = $name;
			}

			if (isset($value[$schema->Name])) foreach ($fields as $field) {
				$fName = $field->Name();
				$sName = $namesMap[$fName];

				if (array_key_exists($sName, $value[$schema->Name])) {
					$field->setValue($value[$schema->Name][$sName], $value[$schema->Name]);
				}
			}

			foreach ($fields as $field) {
				$name = $namesMap[$field->Name()];
				$metadata[$schema->Name][$name] = $field->dataValue();
			}
		}

		$record->{$this->name} = serialize($metadata);
	}

	public function FieldHolder() {
		Requirements::javascript(THIRDPARTY_DIR . '/jquery/jquery.js');
		Requirements::javascript(THIRDPARTY_DIR . '/jquery-livequery/jquery.livequery.js');
		Requirements::javascript(Director::protocol() . 'ajax.googleapis.com/ajax/libs/jqueryui/1.8.1/jquery-ui.min.js');
		Requirements::javascript('metadata/javascript/MetadataSetField.js');

		Requirements::css(Director::protocol() . 'ajax.googleapis.com/ajax/libs/jqueryui/1.8.1/themes/base/jquery-ui.css');
		Requirements::css('metadata/css/MetadataSetField.css');

		return $this->renderWith('MetadataSetField');
	}

	/**
	 * @return DataObjectSet
	 */
	public function Schemas() {
		$record  = $this->form->getRecord();
		$schemas = $record->getSchemas();
		$result  = new DataObjectSet();

		foreach ($schemas as $schema) {
			$fields = new DataObjectSet();

			foreach ($schema->getFormFields() as $field) {
				$brPos  = strrpos($field->Name(), '[');
				$scName = substr($field->Name(), $brPos + 1, -1);

				$field->setValue($record->Metadata($schema->Name, $scName));
				$fields->push($field);
			}

			$result->push(new ArrayData(array(
				'Title'  => $schema->Title,
				'Fields' => $fields
			)));
		}

		return $result;
	}

}