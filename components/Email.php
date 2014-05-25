<?php

namespace component;

/**
 * componentEmail
 * 
 * Sending different emails
 */
class Email
{
    /**
     * Instance for work with URI
     *
     * @access protected
     * @var Router
     */
    protected $router;
    /**
     * core\Config
     *
     * @access protected
     * @var core\Config
     */
    protected $config;
    
    /**
     * URL of project
     *
     * @access private
     * @var string
     */
    private $project_host;
    /**
     * Title of project
     *
     * @access private
     * @var string
     */
    private $project_title;
    /**
     * Email of project
     *
     * @access private
     * @var string
     */
    private $project_email;
    
    /**
     * Construct
     */
    public function __construct($config, $router)
    {
        $this->router = $router;
        $this->config = $config;
        
        $this->project_host = $this->config->get('project_host');
        $this->project_title = $this->config->get('project_title');
        $this->project_email = $this->config->get('project_email');
    }
    
    /**
     * Sample email
     * 
     * @param string $name
     * @param string $email
     * @return boolean
     */
    public function sample($name, $email)
    {
        $title = 'Sample email';
        
        $content = '<html>'.
            '<head>'.
            '<title>'. $title .'</title>'.
            '</head>'.
            '<body>'.
            '<h1>'. $title .'</h1>'.
            'This is a sample email. Hello World.';
        
        return $this->send($name, $email, $this->project_title . ' - ' . $title, $content);
    }
    
    /**
     * Signature
     * 
     * @access private
     * @return string
     */
    private function signature()
    {
        return '<br><br><small>This e-mail was generated automatically. Copyright &copy; ' . date('Y') . ' ' . $this->project_title;
    }
    
    /**
     * Headers for email
     * 
     * @access private
     * @param string $nick
     * @param string $email
     * @return string
     */
    private function headers($nick, $email)
    {
        return ( 
            'MIME-Version: 1.0' . "\r\n".
            'Content-type: text/html; charset=utf-8' . "\r\n".
            'From: ' . $this->project_title . ' <' . $this->project_email . '>' . "\r\n" .
            'Reply-To: ' . $this->project_title . ' <' . $this->project_email . '>' . "\r\n" .
            'To: ' . $nick . ' <' . $email . '>' . "\r\n".
            'X-Mailer: PHP/' . phpversion());
    }
    
    /**
     * Send email
     * 
     * @access private
     * @param string $nick
     * @param string $email
     * @param string $title
     * @param string $content
     * @param boolean $headers
     * @param boolean $signature
     * @return boolean
     */
    private function send($nick, $email, $title, $content, $headers = true, $signature = true)
    {
        if ($signature)
        {
            $content .= $this->signature();
        }
        $content .= '</body></html>';
        
        if ($headers)
        {
            $headers = $this->headers($nick, $email);
        }
        else
        {
            $headers = '';
        }
        
        return mail($email, $title, $content, $headers);
    }
    
}
