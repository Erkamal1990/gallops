<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
class Email_model extends CI_Model{
   function __construct(){
      parent::__construct();
      $this->load->helper('path');
   }
   function master_template($content){
      $html = "";
      $html .= '<div style="background-color: #EEEEEE; padding-bottom: 30px;">';
      $html .= '<div style="text-align: center">';
      $html .= '<a href="'.BASE_URL.'">';
      $html .= '<img style="padding: 0px 0;height:100px;" src="'.BASE_URL.'assets/img/logo.png">';
      $html .= '</a>';
      $html .= '</div>';
      $html .= '<div style="max-width: 600px; border: solid 1px #ccc; background-color: #fff; margin: 0 auto;">';
      $html .= $content;
      $html .= '</div>';
      $html .= '</div>';
      return $html;
   }
   
   
   function do_email($msg=NULL, $sub=NULL, $to=NULL, $from=NULL, $attachments=NULL, $bccs = NULL){ 
      if ($attachments){
         $attachments = array_unique($attachments);
      }
      
      $from =  "test@test.com";
      $ci = get_instance();
      $ci->load->library('email');
      $config['protocol'] = "smtp";
      $config['smtp_host'] = "ssl://smtp.gmail.com";
      $config['smtp_port'] = "465";
      $config['smtp_user'] = "test@test.in";
      $config['smtp_pass'] = "Admin2015$";
      
      $config['mailtype'] = "html";
      $config['charset'] = "utf-8";
      $config['wordwrap'] = TRUE;
      $config['newline'] = "\r\n";
      $config['crlf'] = "\n";
      
      $ci->email->initialize($config);
      $system_name = SYSTEM_NAME;
      $ci->email->clear(TRUE);
      $ci->email->from($from,SYSTEM_NAME);
      $ci->email->to($to);
      //$ci->email->cc('mayur@test.in');
      if($bccs){
         $ci->email->bcc($bccs);
      }
      
      $ci->email->subject($sub);
      $ci->email->message($msg);
      foreach ($attachments as $attachment) {
         if ($attachment) {
            $ci->email->attach($attachment);
         }
      }
      
      $IsSendMail = $ci->email->send();
      if (!$IsSendMail) {
         //echo ($this->email->print_debugger());
         return $returnvalue = 1;
      } else {
         return $returnvalue = 1;
      }
   }
}