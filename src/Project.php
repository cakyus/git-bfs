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

class Project {

	protected $path;

	public function __construct() {
		$this->path = getcwd();
	}

	public function getConfig() {
		$config = new \GitBfs\Config;
		$config->open($this);
		return $config;
	}

	public function getView() {
		$view = new \GitBfs\View;
		$view->open($this);
		return $view;
	}

	public function validateFile($file){
		if ($file == $this->path.'/bfs.json'){ return false; }
		$config = $this->getConfig();
		foreach ($config->filters as $filter){
			if (fnmatch($filter, $file)){
				return true;
			}
		}
		return false;
	}

	public function getPath(){
		return $this->path;
	}

	public function getRemote($remoteName){

		$remote = new \GitBfs\Remote;
		if ( $remote->open($this, $remoteName) === false){
			return false;
		}

		return $remote;
	}
}
