<?php

namespace components;

use core\View,
    core\Config,
    helpers\Validation;

/**
 * Notification
 * 
 * Sending different service/notification emails
 * 
 * <pre>
 * $_email = new Email();
 * $_email->setTo('john.doe@email.com', 'John Doe')->setTitle('Do not read this')->sample();
 * </pre>
 */
class Email
{
    /**
     * Where to send email
     *
     * @var array
     */
    private $emails = array();
    /**
     * Title of email
     * 
     * @var string
     */
    private $title = '';
    /**
     * Where to reply
     *
     * @var array
     */
    private $reply = [];
    
    /**
     * If want call reset after send
     *
     * @var boolean
     */
    private $resetAfterSend = true;
    
    /**
     * Construct
     */
    public function __construct()
    {
        $this->setTitle();
    }
    
    /**
     * Set to
     * 
     * @param string $email
     * @param string $name
     * @return Email
     */
    public function setTo($email, $name = '')
    {
        if ( Validation::isEmail($email) ) {
            empty($name) ? ($this->emails[] = $email) : ($this->emails[$name] = $email);
        }
        
        return $this;
    }
    
    /**
     * Set reply address
     * 
     * @param string $email
     * @param string $name
     * @return Email
     */
    public function setReply($email, $name = '')
    {
        if (Validation::isEmail($email)) {
            empty($name) ? ($this->reply[] = $email) : ($this->reply[$name] = $email);
        }
        
        return $this;
    }
    
    /**
     * Set title
     * 
     * @param string $title
     * @return Email
     */
    public function setTitle($title = '', $prefix = true)
    {
        $titleParts = array();
        if ( $prefix ) {
            $titleParts[] = Config::gi()->get('project_title');
        }
        
        if ( !empty($title) ) {
            $titleParts[] = $title;
        }
        
        $this->title = implode(' - ', $titleParts);
        return $this;
    }
    
    /**
     * Set to call reset after send
     * 
     * @param boolean $reset
     */
    public function setResetAfterSend($reset = true)
    {
        $this->resetAfterSend = (boolean) $reset;
    }
    
    /**
     * Standart send any email allowed in lookuptable and exists
     * 
     * @param string $template
     * @param array $variables
     * @return boolean
     */
    public function __call( $template, $variables = array() )
    {
        $output = false;
        
        if (!empty($variables) && count($variables) == 1 && array_key_exists(0, $variables))
            $variables = $variables[0];

        if ( !empty($this->title) && !isset($variables['title']) ) {
            $variables['title'] = $this->title;
        }

        $content = View::renderElement('email/' . $template, $variables, true);
        if ( !empty($content) ) {
            $output = $this->send($content);
        }
        
        return $output;
    }
    
    /**
     * Reset email settings
     */
    public function reset()
    {
        $this->emails = array();
        $this->reply = array();
        $this->setTitle();
    }
        
    /**
     * Headers for email
     * 
     * @return string
     */
    private function headers()
    {
        $prTitle = Config::gi()->get('project_title');
        $prEmail = Config::gi()->get('project_email');
        
        $output = 'MIME-Version: 1.0' . "\r\n";
        $output .= 'Content-type: text/html; charset=utf-8' . "\r\n";
        $output .= 'From: ' . $prTitle . ' <' . $prEmail . '>' . "\r\n";
        
        $reply = [];
        if (!empty($this->reply)) {
            foreach ($this->reply as $k => $r)
                $reply[] = empty($k) || is_numeric($k) ? $r : ($k . ' <' . $r . '>');
        }
        else
            $reply[] = $prTitle . ' <' . $prEmail . '>';
        
        if (!empty($reply))
            $output .= 'Reply-To: ' . implode(',', $reply) . "\r\n";
        
        $output .= 'X-Mailer: PHP/' . phpversion();
        
        return $output;
    }
    
    /**
     * Send email
     * 
     * @param string $content
     * @param boolean $headers
     * @return boolean
     */
    private function send($content, $headers = true)
    {
        $output = false;
        
        if ( !empty($this->emails) ) {
            $headers = $headers ? $this->headers() : '';
            
            $emails = array();
            foreach ( $this->emails AS $name => $email ) {
                if ( empty($name) || is_numeric($name) ) {
                    $emails[] = $email;
                } else {
                    $emails[] = $name . ' <' . $email . '>';
                }
            }
            
            //var_dump(implode(', ', $emails), $this->title, $content, $headers);
            $output = mail(implode(', ', $emails), $this->title, $content, $headers);
            if ( $this->resetAfterSend ) {
                $this->reset();
            }
        }
        
        return $output;
    }
    
}
