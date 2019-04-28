<?php

/**
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 **/

namespace GitBfs;

class Config {

	protected $project;

	public $version;
	public $files;
	public $filters;
	public $stash;

	public function __construct() {

		$this->version = '1';
		$this->files = array();
		$this->filters = array();
		$this->indexes = array();
		$this->stash = array();
	}

	public function open(\GitBfs\Project $project) {

		$this->project = $project;
		$configPath = $this->getPath();

		if (is_file($configPath) == false){ return true; }

		$text = file_get_contents($configPath);
		$data = json_decode($text, true);

		if (json_last_error()){
			throw new \Exception("JSON Parse error: ".json_last_error_msg());
		}

		if (empty($data['version'])){
			throw new \Exception("Invalid format: version is empty");
		}

		if ($data['version'] != $this->version){
			throw new \Exception("Invalid version");
		}

		foreach (array('filters', 'files') as $configName){
			if (empty($data[$configName])){
				$data[$configName] = $this->$configName;
			} else {
				$this->$configName = $data[$configName];
			}
		}
	}

	public function getPath(){
		return $this->project->getPath().'/bfs.json';
	}

	public function save(){

		$data = new \stdClass;
		$data->version = $this->version;
		$data->filters = $this->filters;
		$data->files = $this->files;

		ksort($data->files);

		$text = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
		file_put_contents($this->getPath(), $text);
	}
}
