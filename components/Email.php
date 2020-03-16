<?php

namespace components;

use core\View,
    helpers\Validation;
use PHPMailer\PHPMailer;

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
    private $emails = array();
    private $title = '';
    private $reply = [];
    private $cc = [];
    private $resetAfterSend = true;
    private $pictures = [];
    private $attachments = [];
    private $content;
    private $titleIndex = 0;
    private $titleParams = [];

    /**
     * @var \core\Config
     */
    private $config;
    
    /**
     * Construct
     */
    public function __construct()
    {
        $this->config = \core\Config::gi();
    }

    /**
     * Set to
     * 
     * @param string $email
     * @param string $name
     * @return Email
     */
    public function setTo(string $email, string $name = ''): Email
    {
        if (Validation::isEmail($email)) {
            empty($name) ? ($this->emails[] = $email) : ($this->emails[$name] = $email);
        }

        return $this;
    }

    /**
     * Set CC
     *
     * @param string $email
     * @param string $name
     * @return Email
     */
    public function setCC(string $email, string $name = ''): Email
    {
        if (Validation::isEmail($email)) {
            empty($name) ? ($this->cc[] = $email) : ($this->cc[$name] = $email);
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
    public function setReply(string $email, string $name = ''): Email
    {
        if (Validation::isEmail($email)) {
            empty($name) ? ($this->reply[] = $email) : ($this->reply[$name] = $email);
        }

        return $this;
    }

    /**
     * Set title
     * 
     * @uses ..\config\main.lt.php
     * @param int $i
     * @param mixed $vars
     * @return \components\Email
     */
    public function setTitle(int $i, ...$vars): Email
    {
        $this->titleIndex = $i;
        $this->titleParams = $vars;
        return $this;
    }

    /**
     * Add picture
     * @param string $file
     * @param string $cid
     * @return Email
     */
    public function addPicture(string $file, string $cid): Email
    {
        $this->pictures[$cid] = $file;
        return $this;
    }

    /**
     * @param string $filename
     * @param string $path
     * @return Email
     */
    public function addAttachment(string $filename, string $path): Email
    {
        $this->attachments[$filename] = $path;
        return $this;
    }

    /**
     * Set to call reset after send
     * 
     * @param bool $reset
     */
    public function setResetAfterSend(bool $reset = true): Email
    {
        $this->resetAfterSend = $reset;
        return $this;
    }

    /**
     * Send any email
     *
     * @param string $template
     * @param array $variables
     * @return boolean
     */
    public function __call($template, $variables = array())
    {
        $output = false;

        if (!empty($variables) && count($variables) == 1 && array_key_exists(0, $variables))
            $variables = $variables[0];
        
        //set email language by employee
        $employee = Employee::gi(reset($this->emails));
        if (!$employee->empty())
            \helpers\l10n::set($employee->raw()['language'] ?? 'cs');

        $this->composeTitle();
        
        if (!empty($this->title) && !isset($variables['title'])) {
            $variables['title'] = $this->title;
        }
        
        $variables['project_host'] = \core\Router::gi()->getHost();
        
        $this->content = (new View('/templates/email/' . $template, $variables))->render();
        if (!empty($this->content)) {
            //auto add pictures
            if (preg_match_all(@"/\"cid:([^\"]+)\"/", $this->content, $matches) > 0) {
                foreach ($matches[1] as $match) {
                    if (file_exists(BASE_PATH . DS . 'templates' . DS . 'email' . DS . $template . DS . $match))
                        $this->addPicture(BASE_PATH . DS . 'templates' . DS . 'email' . DS . $template . DS . $match, $match);
                }
            }

            try {
                $output = $this->send();
            } catch (\PHPMailer\Exception $e) {
                \core\Debug::var_dump($e->getMessage());
            }
        }
        
        \helpers\l10n::reset();

        return $output;
    }
    
    /**
     * Compose email subject
     */
    private function composeTitle()
    {
        $title = $lt = $this->config->lt('email_titles.' . \helpers\l10n::lang() . '.' . $this->titleIndex);
        if (!empty($title)) {
            foreach ($this->titleParams as $key => $param) {
                $title = str_replace('$' . $key, $param, $title);
            }
            
            $this->title = $title;
        }
    }

    /**
     * Reset email settings
     */
    public function reset()
    {
        $this->emails = [];
        $this->reply = [];
        $this->pictures = [];
        $this->attachments = [];
        $this->cc = [];
        $this->title = '';
        $this->content = null;
        $this->titleIndex = 0;
        $this->titleParams = [];
    }

    /**
     * Send email
     * @return bool
     * @throws \PHPMailer\Exception
     */
    private function send(): bool
    {
        $output = false;

        if (!empty($this->emails)) {
            $prTitle = $this->config->get('project_title');
            $prEmail = $this->config->get('project_email');

            $mailer = new PHPMailer(true);
            $mailer->CharSet = PHPMailer::CHARSET_UTF8;
            $mailer->Encoding = PHPMailer::ENCODING_BASE64;

            //autoload config
            $c = $this->config->get('mailer');
            if (!empty($c) && is_array($c)) {
                foreach ($c as $key => $value) {
                    if (is_int($key) && method_exists($mailer, $value)) {
                        call_user_func([$mailer, $value]);
                    } elseif (property_exists($mailer, $key)) {
                        $mailer->{$key} = $value;
                    }
                }
            }

            $mailer->setFrom($prEmail, $prEmail);

            foreach ($this->emails as $name => $email)
                $mailer->addAddress($email, is_numeric($name) ? '' : $name);
            foreach ($this->cc as $name => $email)
                $mailer->addCC($email, is_numeric($name) ? '' : $name);
            foreach ($this->reply as $name => $email)
                $mailer->addReplyTo($email, is_numeric($name) ? '' : $name);
            if (empty($this->reply))
                $mailer->addReplyTo($prEmail, $prTitle);

            $mailer->isHTML(true);
            $mailer->Subject = $this->title;
            $mailer->Body = $this->content;

            foreach ($this->attachments as $filename => $path) {
                $dot = strrpos($filename, '.');
                $mailer->addAttachment($path, $filename, PHPMailer::ENCODING_BASE64, PHPMailer::_mime_types(substr($filename, $dot + 1)));
            }

            foreach ($this->pictures as $cid => $file) {
                $mailer->addEmbeddedImage($file, $cid, pathinfo($file, PATHINFO_BASENAME));
            }

            $output = $mailer->send();

            if ($this->resetAfterSend) {
                $this->reset();
            }
        }

        return $output;
    }
}
