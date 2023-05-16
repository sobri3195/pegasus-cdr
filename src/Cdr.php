<?php
/**
 * Created by PhpStorm.
 * User: rk
 * Date: 17.06.18
 * Time: 21:37
 */

class Cdr
{
    /*
     * @var PDO $conn
     * @var PDO $freepbx
     * */
    private $conn = NULL;
    private $freepbx = NULL;
    public $didnumbers = array();
    public $calls = array();
    public $allnumbers = array();
    public $internals = array();

    public function __construct($mdsn, $user, $pass){
        try {
            $this->conn = new PDO($mdsn, $user, $pass);
            $len = strlen($mdsn);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->freepbx = new PDO(substr($mdsn,0,$len-5), $user, $pass);
            $this->freepbx->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->loadsipusers();
        }
        catch(PDOException $e)
        {
            echo "Connection failed: " . $e->getMessage();
        }
    }

    /**
     * @param $from string
     * @param $to string
     * @param string $external
     * @param string $internal
     * @return bool
     */
    private function loadCalls($from, $to, $external='', $internal=''){
        require_once ("call.class.php");
        require_once ("callgroup.class.php");
        if ($this->conn == NULL) return false;
        $sql = "SELECT * FROM cdr WHERE 
            lastapp IN ('Dial', 'Queue') 
            AND dstchannel NOT LIKE 'Local/FMGL%' 
            AND calldate >= '{$from} 00:00:00' 
            AND calldate <= '{$to} 23:59:59'  
            ORDER BY calldate";
        $loadedIDs = array();
        $records = array();
        foreach ($this->conn->query($sql,PDO::FETCH_ASSOC) as $record){
            if (strlen($record['src']) > 4 && strlen($record['dst']) > 4) continue;
            $id = $record['calldate'] . $record['src'];
            if (!in_array($id, $loadedIDs)) {
                $records[$id] = array();
                $loadedIDs[] = $id;
            }
            $records[$id][] = $record;
        }
        foreach ($records as $record) {
            $call = new Call();
            $call->loadFromArray($record);
            if (!$external && !$internal){
                $this->calls[] = $call;
                $this->allnumbers[$call->getExternalNumber()] = "ext";
            } elseif ($external && $external == $call->did) {
                $this->calls[] = $call;
                $this->allnumbers[$call->getExternalNumber()] = "ext";
            } elseif ($internal && $call->isInternalNumber($internal)) {
                $this->calls[] = $call;
                $this->allnumbers[$call->getExternalNumber()] = "ext";
            }
        }
        return true;
    }

    private function loadsipusers() {
        $sql = "SELECT data FROM sip WHERE keyword='account' AND id IN 
			(SELECT id FROM sip WHERE keyword='context' AND data LIKE 'from-intern%')";
            $this->internals = $this->freepbx->query($sql)->fetchAll(PDO::FETCH_COLUMN);


        $sql = "SELECT data FROM sip WHERE keyword='account' 
                  AND id IN (SELECT id FROM sip WHERE keyword='fromdomain' AND data != '')";
        $this->didnumbers = $this->freepbx->query($sql)->fetchAll(PDO::FETCH_COLUMN);
    }

    public function run(){
        $from = (isset($_GET['from'])) ? $_GET['from'] : date('Y-m-d');
        $to = (isset($_GET['to'])) ? $_GET['to'] : date('Y-m-d');
        $internal = (isset($_GET['internal'])) ? $_GET['internal'] : '';
        $external = (isset($_GET['external'])) ? $_GET['external'] : '';
        $internal = ($internal == '0') ? '' : $internal;
        $external = ($external == '0') ? '' : $external;
        $this->loadCalls($from, $to, $external, $internal);
        $callGroups = array();
        foreach ($this->allnumbers as $key => $val) {
            if (!$key) continue;
            $group = new CallGroup();
            $group->setNumber($key);
            $group->loadCalls($this->calls);
            $callGroups[] = $group;
        }
        asort($this->didnumbers);
        asort($this->internals);
        $intertnals = $this->internals;
        $dids = $this->didnumbers;
        include(dirname(__DIR__) . "/templates/index.phtml");
    }

}
