<?php
/*
 * CandidateReference.php
 * Base model for candidate reference
 * Data model for transfer between WorldApp and Bullhorn
 *
 * Copyright 2015
 * @category    Stratum
 * @package     Stratum
 * @copyright   Copyright (c) 2015 North Creek Consulting, Inc. <dave@northcreek.ca>
 *
 */

namespace Stratum\Model;
class CandidateReference extends ModelObject
{
    const XML_PATH_LIST_DEFAULT_SORT_BY     = 'catalog/frontend/default_sort_by';

    /**
     * Array of attributes codes needed for product load
     *
     * @var array of tag/values
     */
    protected $_fields = ['name'=>'',
						  'referenceFirstName'=>'',
						  'referenceLastName'=>'',
						  'id'=>'',
						  'companyName'=>'',
						  'referenceTitle'=>'',
						  'referencePhone'=>'',
						  'referenceEmail'=>'',
                          'candidateTitle'=>'',
						  'customTextBlock1'=>'',
                          'employmentStart'=>'',
                          'employmentEnd'=>'',
						  'isDeleted'=>''
						  ];

	//OVERRIDE
	public function set($attribute, $value) {
		if ($attribute == "name" || $attribute == "referenceName") {
			$this->setName($value); //split name
		} else {
			parent::set($attribute,$value);
		}
		return $this;
	}

    /**
     * Set Name
     *
     * @param string $name
     * @return prospect
     */
    public function setName($name)
    {
		$this->_fields["name"] = $name;
		$name_split = preg_split('#\s+#', $name, null, PREG_SPLIT_NO_EMPTY);
		$this->log_debug(json_encode($name_split));
		if (!empty($name_split[0])) {
			$this->set("referenceFirstName", $name_split[0]);
		}
		if (count($name_split) >= 3) {
			//there is a compound last name
			$lastName = implode(" ", array_slice($name_split, 1, count($name_split)-1));
			$this->set("referenceLastName", $lastName);
		} else if (count($name_split) == 2) {
			$this->set("referenceLastName", $name_split[1]);
		}
		return $this;
	}

	/**
     * Return name
     *
     * @return string
     */
	public function getName()
	{
		$name = $this->get("name");
		if ($name) {
			return $name;
		}
		$first = $this->get("referenceFirstName");
		$last = $this->get("referenceLastName");
		$name .= $first;
		if ($last) {
			$name .= " $last";
		}
		parent::set("name",$name); //no re-setting sub-names
		return $name;
	}

    public function getDateWithFormat($label, $format = "d/m/Y") {
        $date = $this->get($label);
        if (!$date) {
            return "01/01/0001";
        }
        $date = $date / 1000; //int

        $dateObject = new \DateTime();
        $dateObject->setTimeStamp($date);

        $string = $dateObject->format($format);
        $this->log_debug("$label: $string");
        return $string;
    }

	public function getWorldAppLabel($bh, $form) {
		$wa = "";
		$mappings = $form->get("BHMappings");
		if (array_key_exists($bh, $mappings)) {
			$this->log_debug( "Found $bh");
			$qmaps = $mappings[$bh];
			foreach ($qmaps as $qmap) {
				$wa = $qmap->get("WorldAppAnswerName");
				if ($wa) {
					$this->log_debug( "$wa");
				}
			}
		}
		return $wa;
	}

	public function populateFromData($data) {
		foreach ($data as $key=>$value) {
			$this->set($key, $value);
		}
		return $this;
	}

	public function marshalToJSON() {
		$json = $this->marshalToArray();
		$encoded = json_encode($json, true);
		//$this->var_debug($encoded);
		return $encoded;
	}

	public function marshalToArray() {
		$json = [];
		foreach ($this->expose_bullhorn_set() as $attr=>$value) {
			//now we filter based on what we have vs. what Bullhorn knows
            if ((preg_match("/date/", $attr) || strpos($attr, "employment")==0)
                && $value) {
                //need to convert to Unix timestamp
                $this->log_debug("$attr: ".$value);
                $date = \DateTime::createFromFormat("d/m/Y", $value);
                if (!$date) {
                    //assume we're going the other way
                    $date = \DateTime::createFromFormat('U', ($value/1000));
                    if ($date) {
                        $value = $date->format("d/m/Y");
                    } else { //no value, no date
                        $value = '';
                    }
                } else {
                    //no, we want the Unix timestamp
                    $stamp = $date->format('U') * 1000;
                    $value = $stamp;
                }
            }
            if (is_a($value, "\Stratum\Model\ModelObject")) {
				$json[$attr]['id'] = $value->get("id");
			} else {
				$json[$attr] = $value;
			}
		}
        $this->var_debug($json);
		return $json;
	}

    public function expose_bullhorn_set() {
        $set = array(); //array of set fields
        foreach ($this->getBullhornFields() as $field) {
            $value = $this->get($field);
            if (!empty($value)) {
                $set[$field] = $value;
            }
        }
        //$this->log_debug(json_encode($set));
        return $set;
    }

    private function getBullhornFields() {
        $ret = [];
        foreach (array_keys($this->_fields) as $key) {
			//exceptions need to be here
			if ($key == 'name'
                //|| $key == 'specialties'
				) {
			} else {
                $ret[] = $key;
            }
        }
        return $ret;
    }


	public function dump() {
		$this->log_debug( "---------------------------");
		$this->log_debug( "Stratum\Model\CandidateReference:");
		foreach ($this->_fields as $key=>$there) {
			if ($there) {
				$this->log_debug($key.": ");
				$this->var_debug($there);
			}
		}
		$this->log_debug( "---------------------------");
	}

}
