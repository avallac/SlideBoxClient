<?php

namespace Dexter\SlideBoxClient;

class SlideBoxClient
{
    const COMMAND_SEARCH = 20;
    const COMMAND_INSERT = 30;

    protected $socket;

    public function __construct($host, $port)
    {
        if (($socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) === false) {
            throw new \Exception(socket_strerror(socket_last_error()));
        }
        if (($result = socket_connect($socket, $host, $port)) === false) {
            throw new \Exception(socket_strerror(socket_last_error($socket)));
        }
        $this->socket = $socket;

    }

    public function search($descriptions, $distance)
    {
        $return = [];
        $message = pack("QLL", self::COMMAND_SEARCH, $distance, count($descriptions));
        foreach ($descriptions as $e) {
            $message .= $e;
        }
        if (socket_write($this->socket, $message, strlen($message)) === false) {
            throw new \Exception(socket_strerror(socket_last_error($this->socket)));
        }
        if (($head = socket_read($this->socket, 4)) === '') {
            throw new \Exception('Bad data');
        }
        $size = unpack("L", $head)[1];
        for ($i = 0; $i < $size; $i++) {
            if (($tmp = socket_read($this->socket, 8)) === '') {
                throw new \Exception('Bad data');
            }
            $out = unpack("Lid/Lcount", $tmp);
            $return[$out['id']] = $out['count'];
        }
        return $return;
    }

    public function insert($descriptions, $imgId)
    {
        $message = pack("QLL", self::COMMAND_INSERT, $imgId, count($descriptions));
        foreach ($descriptions as $e) {
            $message .= $e;
        }
        if (socket_write($this->socket, $message, strlen($message)) === false) {
            throw new \Exception(socket_strerror(socket_last_error($this->socket)));
        }
        $tmp = socket_read($this->socket, 8);
        if ($tmp === '' || $tmp === false) {
            throw new \Exception('Bad data');
        }
        $count = unpack("Q", $tmp)[1];
        return $count;
    }
}