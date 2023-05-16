<?php


class Call {
    public $calldate = NULL;
    public $srcNumber = 0;
    /**
     * @var string[] $dstlist
    */
    public $dstlist = array();
    public $status = false;
    public $dstNumber = '';
    public $recordingfile = '';
    public $duration = 0;
    public $did = '';
    public $code = '';
    public $minlength = 0;
    public $filesize = 0;

    /**
     * @param string $code
     */
    public function __construct($code='8843')
    {
        $this->code = $code;
        $this->minlength = 11 - strlen($code);
    }

    /**
     * @param array
    */
    public function loadFromArray($records)
    {
        foreach ($records as $record) {
            if ($record['lastapp'] == 'Dial') {
                $this->srcNumber = (!$this->srcNumber) ? $this->__getNumber($record['src']) : $this->srcNumber;

                if (substr($record['dstchannel'],0, 4) == 'SIP/') {
                    $dstchanend = strpos($record['dstchannel'], '-');
                    if ($dstchanend) {
                        $did = substr($record['dstchannel'], 4, $dstchanend - 4);
                        $this->did = strlen($did) > strlen($this->did) ? $did : $this->did;
                    }
                }
                $BASE_DIR = dirname(dirname(__FILE__));
                $this->calldate = (isset($record['calldate'])) ? $record['calldate'] : $this->calldate;
                $path = "records/";
                $time = strtotime($this->calldate);
                $path .= date("Y/m/d/", $time);
                $recfilename = $path . trim($record['recordingfile']);
                $recfilesize = is_file($BASE_DIR . "/" . $recfilename) ? filesize($BASE_DIR . "/" . $recfilename) : 0;
                $this->recordingfile = $recfilesize > $this->filesize ? $recfilename : $this->recordingfile;
                $this->filesize = $recfilesize > $this->filesize ? $recfilesize : $this->filesize;
                $this->status = ($record['billsec'] > 2) ? true : $this->status;
                if ($this->status) {
                    $this->dstNumber = (isset($record['dst'])) ? $this->__getNumber($record['dst']) : $this->dstNumber;
                    $this->duration = $this->duration < $record['billsec'] ? $record['billsec'] : $this->duration;
                } else {
                    $this->dstlist[] = $this->__getNumber($record['dst']);
                }
            } elseif(substr($record['channel'],0, 4) == 'SIP/') {
                $dstchanend = strpos($record['channel'], '-');
                $did =  substr($record['channel'], 4, $dstchanend-4);
                $this->did = strlen($did) > strlen($this->did) ? $did : $this->did;
            }

            if (substr($record['lastdata'],0, 4) == 'SIP/') {
                $dstchanend = strpos($record['lastdata'], '/', 4);
                if ($dstchanend) {
                    $did = substr($record['lastdata'], 4, $dstchanend - 4);
                    $this->did = strlen($did) > strlen($this->did) ? $did : $this->did;
                }
            }
        }
        $this->dstlist = array_unique($this->dstlist);
    }

    /*
     * @param string $text
     * @return string
     * */
    private function __getNumber($text)
    {
        $length = strlen($text);
        if ($length < $this->minlength) {
            return $text;
        } elseif ($length > 9) {
            return "8" . substr($text, -10);
        } elseif ($length == $this->minlength) {
            return $this->code . $text;
        }
        return "";
    }

    public function getExternalNumber(){
        if (strlen($this->srcNumber) > 4) return $this->srcNumber;
        if (strlen($this->dstNumber) > 4) return $this->dstNumber;
        if (isset($this->dstlist[0]) && strlen($this->dstlist[0]) > 4) return $this->dstlist[0];
        return "";
    }

    public function isInternalNumber($inter){
        if ($this->srcNumber == $inter) return true;
        if ($this->dstNumber == $inter) return true;
        return in_array($inter, $this->dstlist);
    }
    /*
     * @return string
     * */
    public function  __toString()
    {
        // TODO: Implement __toString() method.
        return "date: $this->calldate  src: $this->srcNumber";
    }

}
