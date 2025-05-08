<?php

// Package namespace.
namespace NotificationChannels\Ippanel;

use DateTimeInterface; // Import DateTimeInterface

class IppanelMessage
{
    /**
     * The text content of the message for simple SMS.
     *
     * @var string|null
     */
    public $text;

    /**
     * The recipient number(s). Can be a single number or an array.
     *
     * @var string|array
     */
    public $to;

    /**
     * The sender number (خط فرستنده). Overrides the default config sender.
     *
     * @var string|null
     */
    public $from;

    /**
     * The pattern code for pattern-based SMS.
     *
     * @var string|null
     */
    public $patternCode;

    /**
     * The variables for pattern-based SMS.
     * This should be an associative array where keys match pattern variables.
     *
     * @var array
     */
    public $variables = [];

    /**
     * Optional time for scheduling the message.
     *
     * @var DateTimeInterface|string|null
     */
    public $time;


    /**
     * Set the message text for a simple SMS.
     *
     * @param string $text The message content.
     * @return $this
     */
    public function text($text)
    {
        $this->text = $text;
        return $this;
    }

    /**
     * Set the recipient number(s).
     *
     * @param string|array $to A single phone number or an array of phone numbers.
     * @return $this
     */
    public function to($to)
    {
        $this->to = $to;
        return $this;
    }

    /**
     * Set the sender number for this specific message.
     *
     * @param string $from The sender number.
     * @return $this
     */
    public function from($from)
    {
        $this->from = $from;
        return $this;
    }

    /**
     * Set the pattern code for a pattern-based SMS.
     *
     * @param string $patternCode The pattern code from IPPanel.
     * @return $this
     */
    public function pattern($patternCode)
    {
        $this->patternCode = $patternCode;
        return $this;
    }

    /**
     * Set the variables for a pattern-based SMS.
     *
     * @param array $variables Associative array of variable names and values.
     * @return $this
     */
    public function variables(array $variables)
    {
        $this->variables = $variables;
        return $this;
    }

    /**
     * Set the time for scheduling the message.
     *
     * @param DateTimeInterface|string $time The time to send the message.
     * @return $this
     */
    public function time($time)
    {
        $this->time = $time;
        return $this;
    }

    /**
     * Check if the message is pattern-based.
     *
     * @return bool True if a pattern code is set, false otherwise.
     */
    public function isPatternBased()
    {
        return !empty($this->patternCode);
    }

    /**
     * Check if the message is a simple text message.
     *
     * @return bool True if text is set and no pattern code is set, false otherwise.
     */
    public function isSimpleText()
    {
        return !empty($this->text) && empty($this->patternCode);
    }

    // You can add other methods here based on IPPanel API features,
    // e.g., setting message type (promotional/service), delivery report options, etc.
}
