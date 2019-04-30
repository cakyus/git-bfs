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

class Remote {

	protected $project;
	protected $name;
	protected $url;

	public function __construct(){
		$this->url = 'file:///data/bfs';
	}

	public function open(\GitBfs\Project $project, $remoteName) {
		$this->project = $project;
		$this->name = $remoteName;
		return true;
	}

	public function push() {

		$item = parse_url($this->url);

		// TODO validate protocal , assume "file"
		if (empty($item['path'])){
			throw new \Exception("Path is empty");
		}

		$dir = trim($item['path'], "/");
		$dir = $_SERVER['HOME'].'/'.$dir;

		if (is_dir($dir) == false){
			throw new \Exception("Directory not found: '$dir'");
		}

		$objectDir = $dir.'/objects';
		$infoDir = $dir.'/info';
		$logDir = $dir.'/logs';

		$config = $this->project->getConfig();
		foreach ($config->files as $filePath => $file){

			if (is_file($filePath) == false){
				fwrite(STDERR, "WARN filePath is not found : '$filePath'\n");
				continue;
			}

			$fileHash = $file['hash'];

			$remoteObjectFile = $objectDir.'/'.$fileHash;
			$remoteInfoFile = $infoDir.'/'.$fileHash;
			$remoteLogDir = $logDir.'/'.$fileHash;
			
			// write object file

			if (is_file($remoteObjectFile) == false) {
				fwrite(STDERR, "PUSH object $filePath\n");
				copy($filePath, $remoteObjectFile);
			}

			// write info file

			if (is_file($remoteInfoFile)){
				$data = file($remoteInfoFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
				if (in_array($filePath, $data)){
					continue;
				}
				$data[] = $filePath;
				$text = implode("\n", $data);
				fwrite(STDERR, "PUSH info $filePath\n");
				file_put_contents($remoteInfoFile, $text);
			} else {
				fwrite(STDERR, "PUSH info $filePath\n");
				file_put_contents($remoteInfoFile, $filePath);
			}
		}
	}

	public function pull(){

		$item = parse_url($this->url);

		// TODO validate protocal , assume "file"
		if (empty($item['path'])){
			throw new \Exception("Path is empty");
		}

		$storageDir = trim($item['path'], "/");
		$storageDir = $_SERVER['HOME'].'/'.$storageDir;

		if (is_dir($storageDir) == false){
			throw new \Exception("Directory not found: '$storageDir'");
		}

		$objectDir = $storageDir.'/objects';
		if (is_dir($objectDir) == false){
			throw new \Exception("Directory not found: '$objectDir'");
		}

		$config = $this->project->getConfig();
		foreach ($config->files as $filePath => $file){

			$fileHash = $file['hash'];

			$remoteObjectFile = $objectDir.'/'.$fileHash;
			if (is_file($remoteObjectFile) == false){
				fwrite(STDERR, "WARN remoteObjectFile not found : '$remoteObjectFile'\n");
				continue;
			}

			$localFilePath = $this->project->getPath().'/'.$filePath;
			$localFileDirs = explode('/', $localFilePath);

			array_shift($localFileDirs);
			array_pop($localFileDirs);

			$localFileDirPath = '';
			foreach ($localFileDirs as $localFileDir){
				$localFileDirPath .= '/'.$localFileDir;
				if (is_dir($localFileDirPath) == false){
					mkdir($localFileDirPath);
				}
			}

			if (is_file($localFilePath)){
				continue;
			}

			fwrite(STDERR, "PULL $filePath\n");
			copy($remoteObjectFile, $localFilePath);
		}
	}
}
