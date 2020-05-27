<?php

namespace components;

use core\View,
    core\Config,
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
    private $resetAfterSend = true;
    private $pictures = [];
    private $attachments = [];

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
    public function setTo(string $email, string $name = ''): Email
    {
        if (Validation::isEmail($email)) {
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
     * @param string $title
     * @return Email
     */
    public function setTitle(string $title = ''): Email
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

        if (!empty($this->title) && !isset($variables['title'])) {
            $variables['title'] = $this->title;
        }

        $content = (new View('/templates/email/' . $template, $variables))->render();
        if (!empty($content)) {
            //auto add pictures
            if (preg_match_all(@"/\"cid:([\w\d]+\.\w+)\"/", $content, $matches) > 0) {
                foreach ($matches[1] as $match)
                    $this->addPicture(BASE_PATH . DS . 'templates' . DS . 'email' . DS . $template . DS . $match, $match);
            }

            try {
                $output = $this->send($content);
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
        $this->setTitle();
    }

    /**
     * Send email
     * @param $content
     * @return bool
     * @throws \PHPMailer\Exception
     */
    private function send($content): bool
    {
        $output = false;

        if (!empty($this->emails)) {
            $prTitle = Config::gi()->get('project_title');
            $prEmail = Config::gi()->get('project_email');

            $mailer = new PHPMailer(false);
            $mailer->CharSet = PHPMailer::CHARSET_UTF8;
            $mailer->Encoding = PHPMailer::ENCODING_BASE64;
            \helpers\Utils::applyConfig($mailer, 'mailer');

            $mailer->setFrom($prEmail, $prEmail);

            foreach ($this->emails as $name => $email) {
                if (empty($name) || is_numeric($name))
                    $mailer->addAddress($email);
                else
                    $mailer->addAddress($email, $name);
            }

            if (!empty($this->reply)) {
                foreach ($this->reply as $k => $r)
                    $mailer->addReplyTo($r, empty($k) || is_numeric($k) ? '' : $k);
            } else
                $mailer->addReplyTo($prEmail, $prTitle);

            $mailer->isHTML(true);
            $mailer->Subject = $this->title;
            $mailer->Body = $content;

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
