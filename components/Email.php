<?php

namespace components;

use helpers\Validation;
use PHPMailer\PHPMailer;

/**
 * PHPMailer facade
 * Sending different service/notification emails
 * 
 * <pre>
 * $email = new Email();
 * $email->setTo('john.doe@email.com', 'John Doe')->setTitle('Do not read this')->send(new \core\View('/templates/email/sample', []));
 * </pre>
 *
 * @author Michal Stefanak
 * @link https://github.com/stefanak-michal/DragonMVC
 * @package components
 */
class Email
{
    private $emails = array();
    private $title = '';
    private $reply = [];
    private $cc = [];
    private $bcc = [];
    private $resetAfterSend = true;
    private $pictures = [];
    private $attachments = [];
    private $content;

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
     * Set BCC
     *
     * @param string $email
     * @param string $name
     * @return Email
     */
    public function setBCC(string $email, string $name = ''): Email
    {
        if (Validation::isEmail($email)) {
            empty($name) ? ($this->bcc[] = $email) : ($this->bcc[$name] = $email);
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
     * @param string $title
     * @return Email
     */
    public function setTitle(string $title): Email
    {
        $this->title = $title;
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
     * @return Email
     */
    public function setResetAfterSend(bool $reset = true): Email
    {
        $this->resetAfterSend = $reset;
        return $this;
    }

    /**
     * Send any email
     *
     * @param \core\View $view
     * @return boolean
     */
    public function send(\core\View $view): bool
    {
        $output = false;

        $this->content = $view->render();
        if (!empty($this->content)) {
            //auto add pictures
            if (preg_match_all(@"/\"cid:([^\"]+)\"/", $this->content, $matches) > 0) {
                $path = pathinfo($view->getView(), PATHINFO_DIRNAME) . DS . pathinfo($view->getView(), PATHINFO_FILENAME) . DS;
                foreach ($matches[1] as $match) {
                    if (file_exists($path . $match))
                        $this->addPicture($path . $match, $match);
                }
            }

            try {
                $output = $this->_send();
            } catch (\PHPMailer\Exception $e) {
                \core\Debug::var_dump($e->getMessage());
            }
        }
        
        return $output;
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
        $this->bcc = [];
        $this->title = '';
        $this->content = null;
    }

    /**
     * Send email
     * @return bool
     * @throws \PHPMailer\Exception
     */
    private function _send(): bool
    {
        $output = false;

        if (!empty($this->emails)) {
            $prTitle = \core\Config::gi()->get('project_title');
            $prEmail = \core\Config::gi()->get('project_email');

            $mailer = new PHPMailer(true);
            \core\Config::apply('mailer', $mailer);

            $mailer->setFrom($prEmail, $prEmail);

            foreach ($this->emails as $name => $email)
                $mailer->addAddress($email, is_numeric($name) ? '' : $name);
            foreach ($this->cc as $name => $email)
                $mailer->addCC($email, is_numeric($name) ? '' : $name);
            foreach ($this->bcc as $name => $email)
                $mailer->addBCC($email, is_numeric($name) ? '' : $name);
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
