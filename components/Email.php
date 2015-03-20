<?php

namespace components;

use core\View,
    helpers\Validation;

/**
 * Notification
 * 
 * Sending different service/notification emails
 * @internal For default magic __call is allowed templates list in lookuptable
 * 
 * <pre>
 * $_email = new Email($this->config);
 * $_email->setTo('john.doe@email.com', 'John Doe')->setTitle('Do not read this')->sample();
 * </pre>
 */
class Email
{
    /**
     * core\Config
     *
     * @access protected
     * @var core\Config
     */
    protected $config;
    
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
     * If want call reset after send
     *
     * @var boolean
     */
    private $resetAfterSend = true;
    
    /**
     * Construct
     */
    public function __construct($config)
    {
        $this->config = $config;
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
     * Set title
     * 
     * @param string $title
     * @return Email
     */
    public function setTitle($title = '', $prefix = true)
    {
        $titleParts = array();
        if ( $prefix ) {
            $titleParts[] = $this->config->get('project_title');
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
        
        $templates = $this->config->lt('allowedTemplates.email');
        if ( in_array($template, $templates) ) {
            if ( !empty($this->title) && !isset($variables['title']) ) {
                $variables['title'] = $this->title;
            }

            $content = View::renderElement('email/' . $template, $variables, true);
            if ( !empty($content) ) {
                $output = $this->send($content);
            }
        }
        
        return $output;
    }
    
    /**
     * Reset email settings
     */
    public function reset()
    {
        $this->emails = array();
        $this->setTitle();
    }
        
    /**
     * Headers for email
     * 
     * @return string
     */
    private function headers()
    {
        $prTitle = $this->config->get('project_title');
        $prEmail = $this->config->get('project_email');
        
        return ( 
            'MIME-Version: 1.0' . "\r\n".
            'Content-type: text/html; charset=utf-8' . "\r\n".
            'From: ' . $prTitle . ' <' . $prEmail . '>' . "\r\n" .
            'Reply-To: ' . $prTitle . ' <' . $prEmail . '>' . "\r\n" .
            'X-Mailer: PHP/' . phpversion()
        );
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
