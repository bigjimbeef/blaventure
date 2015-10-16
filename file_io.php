<?php

class FileIO {

	private static function CanLockFile($filePath) {

		$result		= exec("fuser $filePath 2>/dev/null");
		$canLock	= strcasecmp($result, "") == 0;

		return $canLock;
	}

	public static function ReadFile($filePath) {

		if ( !FileIO::CanLockFile($filePath) ) {
			echo "WARNING: File is in use. Wait a second and try again.\n";
			exit(15);
		}

		$handle		= fopen($filePath, "r");
		$serialData = fread($handle, filesize($filePath));

		fclose($handle);

		return $serialData;
	}

	public static function UnserializeFile($filePath) {

		$serialData 	= FileIO::ReadFile($filePath);
		$nonserialData 	= unserialize($serialData);

		return $nonserialData;
	}

	public static function WriteFile($saveData, $filePath){

		if ( !FileIO::CanLockFile($filePath) ) {
			echo "WARNING: File is in use. Wait a second and try again.\n";
			exit(15);
		}

		$handle		= fopen($filePath, "w");
		$serialData = serialize($saveData);

		fwrite($handle, $serialData);

		fclose($handle);
	}
}