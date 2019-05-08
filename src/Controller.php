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

/**
 * Git BIG File System
 **/

class Controller extends \GitBfs\Command {

	public function __construct() {
		parent::__construct();
		$this->version = '1.0';
	}

	/**
	 * Print current working directory status
	 **/

	public function commandStatus() {

		$exitCode = 0;

		$project = new \GitBfs\Project;
		$config = $project->getConfig();
		$view = $project->getView();

		$dirs = array();
		$dirs[] = $project->getPath();

		$deletedFiles = $config->files;

		while (count($dirs)) {

			$dir = array_shift($dirs);
			if ($dh = opendir($dir)) {
				while ( ( $file = readdir($dh) ) !== false ) {
					if ($file == '.' || $file == '..') {
						continue;
					}
					$path = $dir.'/'.$file;
					if (is_dir($path)) {
						if ($file == '.git'){
							continue;
						}
						$dirs[] = $path;
					} elseif (is_file($path)) {

						if ($project->validateFile($path) == false){
							continue;
						}

						$fileStatus = 'I'; // Invalid
						$viewFile = $view->viewPath($path);

						unset($deletedFiles[$viewFile]);

						if (array_key_exists($viewFile, $config->files)) {

							$fileSize = filesize($path);
							$fileDate = filemtime($path);

							$configFileSize = $config->files[$viewFile]['size'];
							$configFileDate = $config->files[$viewFile]['date'];
							$configFileHash = $config->files[$viewFile]['hash'];

							if (	$fileSize == $configFileSize
								&&	$fileDate == $configFileDate
								){
								// No changes
								$fileStatus = ' ';
								continue;
							}

							$fileHash = md5_file($path);

							if ($fileHash == $configFileHash){
								// No changes
								$fileStatus = ' ';
								continue;
							}

							// Has change
							$fileStatus = 'M';
							$exitCode = 1;
						} else {
							// Not in database
							$fileStatus = '?';
							$exitCode = 1;
						}

						echo "$fileStatus $viewFile\n";
						$exitCode = 1;
					}
				}
			}
		}

		foreach ($deletedFiles as $viewFile => $fileHash){
			echo "D $viewFile\n";
			$exitCode = 1;
		}

		return $exitCode;
	}

	/**
	 * Verify file hashes in configuration
	 **/

	public function commandHash(){

		$exitCode = 0;

		$project = new \GitBfs\Project;
		$config = $project->getConfig();

		foreach ($config->files as $filePath => $file){

			if (is_file($filePath) == false){
				fwrite(STDERR, "File not found: '$filePath'\n");
				$exitCode = 1;
				continue;
			}

			$fileHash = md5_file($filePath);
			if ($fileHash != $file['hash']){
				fwrite(STDERR, "Hash have been change : '$filePath'\n");
				$exitCode = 1;
			}
		}

		return $exitCode;
	}

	/**
	 * Save configuration file
	 **/

	public function commandCommit(){

		$project = new \GitBfs\Project;
		$config = $project->getConfig();
		$view = $project->getView();

		$dirs = array();
		$dirs[] = $project->getPath();

		$files = array();

		while (count($dirs)) {

			$dir = array_shift($dirs);
			if ($dh = opendir($dir)) {
				while ( ( $file = readdir($dh) ) !== false ) {
					if ($file == '.' || $file == '..') {
						continue;
					}
					$path = $dir.'/'.$file;
					if (is_dir($path)) {
						$dirs[] = $path;
					} elseif (is_file($path)) {

						if ($project->validateFile($path) == false){ continue; }

						$viewFile = $view->viewPath($path);
						$fileHash = md5_file($path);

						// check config files
						$fileChange = true;
						foreach ($config->files as $configFilePath => $configFile){
							if ($configFilePath != $viewFile){ continue; }
							if ($configFile['hash'] == $fileHash){
								// ignore size and date on hash same
								$fileChange = false;
								$files[$viewFile] = $configFile;
								continue;
							}
						}

						if ($fileChange == false){
							continue;
						}

						$fileSize = filesize($path);
						$fileDate = filemtime($path);

						$files[$viewFile] = array(
							 'hash' => $fileHash
							,'size' => $fileSize
							,'date' => $fileDate
							);
					}
				}
			}
		}

		$config->files = $files;
		$config->save();
	}

	/**
	 * Copy file to remote storage
	 **/

	public function commandPush($remoteName = null){

		$remoteName = 'local';

		$project = new \GitBfs\Project;

		if ( ( $remote = $project->getRemote($remoteName) ) == false){
			fwrite(STDERR, "remote not found: '$remoteName'");
			return false;
		}

		$remote->push();
	}

	/**
	 * Update local files
	 **/

	public function commandPull($remoteName = null){

		$remoteName = 'local';

		$project = new \GitBfs\Project;

		if ( ( $remote = $project->getRemote($remoteName) ) == false){
			fwrite(STDERR, "remote not found: '$remoteName'\n");
			return false;
		}

		$remote->pull();
	}

	/**
	 * Clear files in configuration file
	 **/

	public function commandClear() {
		$project = new \GitBfs\Project;
		$config = $project->getConfig();
		$config->files = array();
		$config->save();
	}

}
