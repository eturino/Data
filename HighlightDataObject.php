<?php

interface EtuDev_Data_HighlightDataObject {

	public function setHighlightData($data);


	public function isHighlighted($origkey);


	public function getDirectHighlighted($origkey);

	static public function getIdField();

}