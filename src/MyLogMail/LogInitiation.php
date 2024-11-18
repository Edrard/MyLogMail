<?php
namespace edrard\MyLogMail;

use edrard\Log\MyLog;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class LogInitiation
{
    protected $config = [];
    protected $mailer = false;
    protected $ports = [ 587 => PHPMailer::ENCRYPTION_STARTTLS, 465 => PHPMailer::ENCRYPTION_SMTPS ];
    protected $ch = 'log';
    protected $handlers;
    protected $re_enable;
    protected $maxfiles;
    protected $imp = ['warning','error','critical'];
    protected $debug = ['debug','info','warning','error','critical'];

    /**
    * put your comment there...
    *
    * @param Config $config
    */
    public function __construct($config, $ch = 'log', array $handlers = array(), $re_enable = false, $maxfiles = 60)
    {
        $this->config =  $config;
        $this->ch = $ch;
        $this->maxfiles = $maxfiles;
        $this->handlers = $handlers;
        $this->re_enable = $re_enable;
        $this->initLog();
    }
    protected function perRunLogs(){
        if($this->config['file']['per_run'] === 1){
            register_shutdown_function([$this, 'renameLog']);
        }
    }
    public function renameLog(){
        $config = MyLog::getLogConfig($this->ch);
        $log_files = $this->getCurrentLogsFiles($config);
        foreach ($log_files as $log) {
            $file = pathinfo($log);
            rename($log, $file['dirname'].'/'.$file['filename'].'_'.date('H_i_s').'.'.$file['extension']);
        }
    }
    /**
    * put your comment there...
    *
    */
    protected function initLog()
    {
        try {
            $this->fileLogSet();
            $this->mailLogSet();
            $this->perRunLogs();
        } catch (Exception $e) {
            MyLog::critical("[".get_class($this)."] ".$e->getMessage(),[],$this->ch);
            die($e->getMessage());
        }
    }
    /**
    * put your comment there...
    */
    protected function fileLogSet()
    {
        MyLog::init($this->config['file']['dst'], $this->ch,$this->handlers,$this->re_enable,$this->maxfiles);
        if ($this->config['file']['full'] != 1 && $this->config['file']['disble'] != 1) {
            MyLog::changeType($this->imp, $this->ch);
            MyLog::info("[".get_class($this)."] Only warnings, errors and criticals",$this->imp,$this->ch);
        }
        if ($this->config['file']['debug'] == 1 && $this->config['file']['disble'] != 1) {
            MyLog::changeType($this->debug, $this->ch);
            MyLog::info("[".get_class($this)."] Debug mode On",$this->debug,$this->ch);
        }
        if ($this->config['file']['disble'] == 1) {
            MyLog::changeType([], $this->ch);
            MyLog::info("[".get_class($this)."] Logs disabled",$this->debug,$this->ch);
        }
        MyLog::info("[".get_class($this)."] Log Initializated with ",['config' => $this->config,'ch' => $this->ch,'max' => $this->maxfiles],$this->ch);
    }
    /**
    * put your comment there...
    *
    */
    public function mailLogSet($shutdown = TRUE)
    {
        $mail = $this->config['mail'];
        if ($mail['user'] && $mail['pass'] && $mail['smtp'] && $mail['port']) {
            $this->mailer = new PHPMailer(true);
            if ($this->config['file']['debug'] == 1) {
                $this->mailer->SMTPDebug = SMTP::DEBUG_SERVER;
            }
            $this->mailer->isSMTP(false);
            $this->mailer->Host       = $mail['smtp'];
            $this->mailer->SMTPAuth   = true;
            $this->mailer->Username   = $mail['user'];
            $this->mailer->Password   = $mail['pass'];
            $this->mailer->SMTPSecure = $this->getMailEncryption($mail['port']);
            $this->mailer->Port       = $mail['port'];

            $this->mailer->setFrom($mail['from']);
            $to = explode(',', $mail['to']);
            foreach($to as $address){
                $this->mailer->addAddress($address);
            }
            $this->mailer->addReplyTo($mail['from']);
            MyLog::info("Setting mail", [], $this->ch);
            if($shutdown !== FALSE){
                register_shutdown_function([$this, 'mailSend']);
            }
        }
    }
    protected function getMailEncryption($port){
        if(isset($this->ports[$port])){
            return $this->ports[$port];
        }
        return null;
    }
    protected function getCurrentLogsFiles(){
        $config = MyLog::getLogConfig($this->ch);
        $logs = glob($config['path'].'/*'.$config['date'].'.log');
        foreach($logs as $key => $log){
            $type = $this->getLogType($log, $config);
            $logs[$type] = $log;
            unset($logs[$key]);
        }
        return $logs;
    }
    /**
    * put your comment there...
    *
    */
    public function mailSend()
    {
        if ($this->mailer === false) {
            return;
        }
        $log_files = $this->getCurrentLogsFiles();
        $read = '';
        foreach ($log_files as $type => $log) {
            $read .= "\n\n".$type."\n\n".file_get_contents($log);
            if ($this->config['mail']['separate'] == 1 || $this->config['mail']['only_important'] == 1) {
                MyLog::info("Sending mail separated", $this->config['mail'], $this->ch);
                if($this->config['mail']['only_important'] == 1 && $this->checkType($type) === FALSE){
                    $read = '';
                    continue;
                }
                $this->sendMailLog($read, '['.$type.' '.$this->config['mail']['subject'].']');
                $read = '';
            }
        }
        if ($this->config['mail']['separate'] != 1 && $this->config['mail']['only_important'] != 1) {
            MyLog::info("Sending mail combined", $this->config['mail'], $this->ch);
            $this->sendMailLog($read, '['.$this->config['mail']['subject'].']');
        }
    }
    protected function checkType($type){
        foreach($this->imp as $t){
            if(strpos($type, $t) !== false){
                return TRUE;
            }
        }
        return FALSE;
    }

    /**
    * put your comment there...
    *
    * @param string $log
    * @param array $config
    */
    protected function getLogType($log, array $config)
    {
        $log =  pathinfo($log);
        $filename = $log['filename'];
        $return = '';
        foreach ($config['types'] as $type => $file) {
            $logname = pathinfo($file);
            $logname = $logname['filename'];
            if (strpos($filename, $logname) !== false) {
                $return .= ' '.$type;
            }
        }
        return $return ? trim($return) : 'unknown';
    }
    /**
    * put your comment there...
    *
    * @param string $text
    * @param string $subject
    */
    protected function sendMailLog($text, $subject)
    {
        if(isset($text) and $text){
            $this->mailer->Subject = $subject;
            $this->mailer->Body    = $text;

            $return = $this->mailer->send();

            MyLog::info("Mail sending result", [$return], $this->ch);
            return $return;
        }
        return False;
    }
}
