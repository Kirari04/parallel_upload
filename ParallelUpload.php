<?php
class ParallelUpload {

    protected $sessionType = null; // how the session is stored
    protected $phpSessionKey = null; // what key the current session key is stored in $_SESSION
    protected $sessionKey = null; // random generated session key

    // variables used during creating new sessions & config
    protected $parts = null; // how many file parts to upload
    protected $tmpFolder = './tmp'; // where to store the uploaded files (better protect the folder)
    protected $tmpFolderPermission = 0777; // permission of folder
    protected $debug = [
        'is_new_session' => null,
        'parts' => null,
        'session' => null,
    ];

    function __construct($config) {
        $this->sessionType = $config['sessionType'] ?? 'php_session';
        $this->phpSessionKey = $config['phpSessionKey'] ?? 'php_session';

        $this->init_session();
        $this->debug['session'] = $this->get_session();
    }

    public function parts(int $parts): ParallelUpload {
        $this->exists_session();
        $this->parts = $parts;
        $this->debug['parts'] = $parts;

        $session = $this->get_session();
        $session->parts = $this->parts;
        $this->set_session($session);

        $this->createTmpFolder($this->tmpFolder, $this->tmpFolderPermission);
        return $this;
    }

    public function upload(int $partNr, $fileContent, string $originalName = "unknown_file.data"): ParallelUpload{
        $this->exists_session();
        $session = $this->get_session();
        $session->originalName = $originalName;
        $session->done = (array)$session->done;
        $this->save_part($partNr, $fileContent);
        $session->done[$partNr] = time();
        $this->debug['session'] = $session;
        $this->set_session($session);
        return $this;
    }

    public function done(): bool{
        $this->exists_session();
        $session = $this->get_session();
        $session->done = (array)$session->done;
        for($i = 1; $i <= $session->parts; $i++){
            if(!isset($session->done[$i])){
                return false;
            }
        }
        $session->end = time();
        $this->set_session($session);
        return true;
    }

    public function merge(string $destination){
        $this->exists_session();
        $session = $this->get_session();
        if($session->end){
            for($i = 1; $i <= $session->parts; $i++){
                file_put_contents($destination, $this->get_part($i, true) , FILE_APPEND | LOCK_EX);
            }
        }
        $this->destroy_session();
        return $destination;
    }

    public function tmpFolder(string $tmpFolder, int  $permission = 0777): ParallelUpload {
        $this->exists_session();
        $this->tmpFolder = $tmpFolder;
        $this->tmpFolderPermission = $permission;

        $session = $this->get_session();
        $session->tmpFolder = $tmpFolder;
        $session->tmpFolderPermission = $permission;
        $this->set_session($session);

        $this->createTmpFolder($tmpFolder, $permission);
        return $this;
    }

    public function dd(): void {
        var_dump($this->debug);
    }

    /**
     * Helper functions
     */
    protected function createTmpFolder(string $tmpFolder, int  $permission = 0777): void {
        if(!file_exists($tmpFolder) || !is_dir($tmpFolder)) {
            mkdir($tmpFolder, $permission, true);
        }
    }

    protected function save_part(int $part, $data): void {
        file_put_contents($this->tmpFolder.'/'.$this->sessionKey.'_'.$part.'.part', $data);
    }

    protected function get_part(int $part, bool $del) {
        $file = $this->tmpFolder.'/'.$this->sessionKey.'_'.$part.'.part';
        $data = file_get_contents($file);
        if($del) unlink($file);
        return $data;
    }


    
    /**
     * SESSION management
     */
    protected function init_session(): void {
        switch ($this->sessionType) {
            case 'php_session':
                $this->start_php_session();
                break;
        }
    }

    protected function destroy_session(): void {
        switch ($this->sessionType) {
            case 'php_session':
                $this->destroy_php_session();
                break;
        }
    }

    protected function exists_session(): void {
        if(!$this->sessionKey){
            throw new Exception('Session could not be found');
        }
        if(!file_exists($this->tmpFolder.'/'.$this->sessionKey)){
            throw new Exception('Session data could not be found');
        }
    }

    protected function get_session(): object {
        $data = @json_decode(file_get_contents($this->tmpFolder.'/'.$this->sessionKey));
        if(gettype($data) !== 'object'){
            $this->reset_session();
            return $this->get_session();
        }
        return $data;
    }

    protected function set_session(array | object $data): void {
        file_put_contents($this->tmpFolder.'/'.$this->sessionKey, json_encode($data));
    }

    protected function rm_session(): void {
        unlink($this->tmpFolder.'/'.$this->sessionKey);
    }

    protected function reset_session(): void {
        $this->set_session([
            'parts' => $this->parts,
            'done' => [],
            'originalName' => null,
            'tmpFolderPermission' => $this->tmpFolderPermission,
            'tmpFolder' => $this->tmpFolder,
            'start' => time(),
            'end' => null
        ]);
    }


    /**
     * PHP build in $_SESSION
     */
    protected function start_php_session(): void {
        if(session_status() == PHP_SESSION_NONE){
            session_start();
        }
        $isNew = false;
        if(!isset($_SESSION[$this->phpSessionKey])){
            $this->debug['is_new_session'] = true;
            $new_session = $this->rand_session_key();
            $_SESSION[$this->phpSessionKey] = $new_session;
            $isNew = true;
        }

        $this->debug['is_new_session'] = $isNew;
        
        $this->sessionKey = $_SESSION[$this->phpSessionKey];
        if($isNew) $this->reset_session();
    }

    protected function destroy_php_session(): void {
        if(session_status() == PHP_SESSION_NONE){
            session_start();
        }

        if(!isset($_SESSION[$this->phpSessionKey])){
            $newSession = $this->rand_session_key();
            $_SESSION[$this->phpSessionKey] = $newSession;
        }
        
        $this->rm_session();
        $this->sessionKey = $_SESSION[$this->phpSessionKey];
    }

    protected function rand_session_key() {
        return hash('sha256', microtime().bin2hex(random_bytes(32)));
    }
}
