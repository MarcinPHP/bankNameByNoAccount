<?php
//Bank account number

class nrb {
	
	private $noAccount = '';
	private $folderData = './';    // folder gdzie będie przetrzymywana lokalny plik z danymi banków
	private $fileData = 'nrb.dat'; // plik z danymi banków
	private $urlBanks = 'https://ewib.nbp.pl/plewibnra?dokNazwa=plewibnra.txt'; // link do aktualnej bazy banków
	private $timeExpired = 10; // Co ile dni aktualizować baze banków
	public $error = '';
	
	public function __construct($noAccount = '') {
		$this->setAccount($noAccount);
	}
	
	/**
	 * Ustaw folder przetrzymywania pliku z danymi banków
	 *
	 * @param string $folder
	 */
	public function setFolderData($folder) {
		$this->folderData = $folder;
	}
	
	
	/**
	 * Ustaw numer konta
	 *
	 * @param string $noAccount
	 */
	public function setAccount($noAccount) {
		$noAccount = preg_replace('/[^0-9]/', '', $noAccount);
		if (isset($noAccount) && !empty($noAccount) && trim($noAccount) != '')
			$this->noAccount = $noAccount;
	}
	
	
	/**
	 * Zwraca zformatowany (ze spacjami) numer konta
	 *
	 * @param string $noAccount
	 * @return string
	 */
	public function format($noAccount = '') {
		$this->setAccount($noAccount);
		return preg_replace('/(.{2})(.{4})(.{4})(.{4})(.{4})(.{4})(.{4})/', '\1 \2 \3 \4 \5 \6 \7', $this->noAccount);
	}
	
	/**
	 * Sprawdza poprawność numeru konta
	 *
	 * @param string $noAccount
	 * @return bool
	 */
	public function correct($noAccount = '') {
		$this->setAccount($noAccount);
		
        // Sprawdzenie czy przekazany numer zawiera 26 znaków
        if(strlen($this->noAccount) != 26) {
            $this->error = "Rachunek nie zawiera 26 cyfr: ".$this->noAccount." (zawiera ".strlen($this->noAccount)." cyfr)";
            return false;
        }
		
        // Zdefiniowanie tablicy z wagami poszczególnych cyfr				
        $weightNumbers = array(1, 10, 3, 30, 9, 90, 27, 76, 81, 34, 49, 5, 50, 15, 53, 45, 62, 38, 89, 17, 73, 51, 25, 56, 75, 71, 31, 19, 93, 57);
        // Dodanie kodu kraju (w tym przypadku dodajemy kod PL)		
        $noAccount = $this->noAccount.'2521';
        $noAccount = substr($noAccount, 2).substr($noAccount, 0, 2); 
        // Wyzerowanie zmiennej
        $sumCheck = 0;
        // Pętla obliczająca sumę cyfr w numerze konta
        for ($i = 0; $i < 30; $i++) {
            $sumCheck += $noAccount[29 - $i] * $weightNumbers[$i];
        }
        // Sprawdzenie czy modulo z sumy wag poszczegolnych cyfr jest rowne 1
        return ($sumCheck % 97 == 1);
	}
	
	/**
	 * Pobierz aktualną baze banków
	 *
	 * @return bool
	 */
	public function downloadBanks() {
		// pobierz aktualny plik:
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->urlBanks);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

		if(curl_exec($ch) === FALSE) {
			$this->error = "Error: " . curl_error($ch);
			return false;
		} else {
			$data = explode("\n", str_replace("\r", '', curl_exec($ch) ));
			$newData = [];
			foreach ($data as $value) {
				//echo mb_detect_encoding($value[1])."<br />\n";
				$value = array_map('trim', explode("\t", $value) );
				$newData[$value[4]] = [	'name'		=> iconv ('CP852', 'utf-8', $value[1]),	// nazwa
										'short'		=> iconv ('CP852', 'utf-8', $value[2]), 	// nazwa skrócona
										'branch'	=> iconv ('CP852', 'utf-8', $value[5]),	// nazwa oddział banku
										'branch_short' 	=> iconv ('CP852', 'utf-8', $value[6]),	// skrót nazwy oddziału banku
										'branch_city'	=> iconv ('CP852', 'utf-8', $value[7]),	// miasto w którym jest oddział
										'address'	=> iconv ('CP852', 'utf-8', $value[8]),		// adres
										'post_code'	=> $value[9],	// kod pocztowy
										'city'		=> iconv ('CP852', 'utf-8', $value[10]),	// miasto
										'BIC'		=> $value[19],
										'BIC_SEPA'	=> $value[20],
										'www'		=> $value[21],	
										'province'	=> iconv ('CP852', 'utf-8', $value[22]) 	// województwo
									];
			}
			file_put_contents($this->folderData.$this->fileData, serialize($newData));
			curl_close($ch);
			return true;
		}
	}
	
	/**
	 * Pobierz dane banków do tablicy
	 *
     * @return array
	 */
	public function getBanks() {
		if (file_exists($this->folderData.$this->fileData)) {
			if ( ( time() - filemtime($this->folderData.$this->fileData) ) > ($this->timeExpired * 86400) ) {
				$this->downloadBanks();
			}
		} 
		else $this->downloadBanks();
			
		return unserialize(file_get_contents($this->folderData.$this->fileData));
	}
	
	/**
	 * Znajduje informacje o banku po numerze konta bankowego
	 *
	 * @param string $noAccount
     * @return array|false
	 */
	public function decodeName($noAccount = '') {
		$this->setAccount($noAccount);
		
		$data = $this->getBanks();
	
		$nrb = mb_substr($this->noAccount, 2, 8);

		if (is_array($data[$nrb])) return $data[$nrb];
		else { $this->error = "Nie znaleziono banku."; return false; }
	}
		
}
