<?php

namespace App\Services\RouterOS;

/**
 * RouterOS PHP API class v1.6
 * Author: Denis Basta
 * http://wiki.mikrotik.com/wiki/API_PHP_class
 */
class RouterosAPI
{
    public $debug = false;

    public $connected = false;

    public $port = 8728;

    public $ssl = false;

    public $timeout = 3;

    /** Batas waktu satu operasi read() (detik) — cegah hang hingga max_execution_time PHP. */
    public $readMaxSeconds = 45;

    public $attempts = 3;

    public $delay = 1;

    public $socket;

    public $error_no;

    public $error_str;

    public function isIterable($var)
    {
        return $var !== null
            && (is_array($var)
                || $var instanceof \Traversable
                || $var instanceof \Iterator
                || $var instanceof \IteratorAggregate);
    }

    public function debug($text)
    {
        if ($this->debug) {
            echo $text . "\n";
        }
    }

    public function encodeLength($length)
    {
        if ($length < 0x80) {
            $length = chr($length);
        } elseif ($length < 0x4000) {
            $length |= 0x8000;
            $length = chr(($length >> 8) & 0xFF) . chr($length & 0xFF);
        } elseif ($length < 0x200000) {
            $length |= 0xC00000;
            $length = chr(($length >> 16) & 0xFF) . chr(($length >> 8) & 0xFF) . chr($length & 0xFF);
        } elseif ($length < 0x10000000) {
            $length |= 0xE0000000;
            $length = chr(($length >> 24) & 0xFF) . chr(($length >> 16) & 0xFF) . chr(($length >> 8) & 0xFF) . chr($length & 0xFF);
        } elseif ($length >= 0x10000000) {
            $length = chr(0xF0) . chr(($length >> 24) & 0xFF) . chr(($length >> 16) & 0xFF) . chr(($length >> 8) & 0xFF) . chr($length & 0xFF);
        }
        return $length;
    }

    public function connect($ip, $login, $password)
    {
        for ($ATTEMPT = 1; $ATTEMPT <= $this->attempts; $ATTEMPT++) {
            $this->connected = false;
            $PROTOCOL = ($this->ssl ? 'ssl://' : '');
            $context = stream_context_create(['ssl' => ['ciphers' => 'ADH:ALL', 'verify_peer' => false, 'verify_peer_name' => false]]);
            $this->debug('Connection attempt #' . $ATTEMPT . ' to ' . $PROTOCOL . $ip . ':' . $this->port . '...');
            $this->socket = @stream_socket_client($PROTOCOL . $ip . ':' . $this->port, $this->error_no, $this->error_str, $this->timeout, STREAM_CLIENT_CONNECT, $context);
            if ($this->socket) {
                stream_set_timeout($this->socket, $this->timeout);
                $this->write('/login', false);
                $this->write('=name=' . $login, false);
                $this->write('=password=' . $password);
                $RESPONSE = $this->read(false);
                if (isset($RESPONSE[0])) {
                    if ($RESPONSE[0] == '!done') {
                        if (!isset($RESPONSE[1])) {
                            $this->connected = true;
                            break;
                        } else {
                            $MATCHES = [];
                            if (preg_match_all('/[^=]+/i', $RESPONSE[1], $MATCHES)) {
                                if ($MATCHES[0][0] == 'ret' && strlen($MATCHES[0][1]) == 32) {
                                    $this->write('/login', false);
                                    $this->write('=name=' . $login, false);
                                    $this->write('=response=00' . md5(chr(0) . $password . pack('H*', $MATCHES[0][1])));
                                    $RESPONSE = $this->read(false);
                                    if (isset($RESPONSE[0]) && $RESPONSE[0] == '!done') {
                                        $this->connected = true;
                                        break;
                                    }
                                }
                            }
                        }
                    }
                }
                fclose($this->socket);
            }
            sleep($this->delay);
        }
        if ($this->connected) {
            $this->debug('Connected...');
        } else {
            $this->debug('Error...');
        }
        return $this->connected;
    }

    public function disconnect()
    {
        if (is_resource($this->socket)) {
            fclose($this->socket);
        }
        $this->connected = false;
        $this->debug('Disconnected...');
    }

    public function parseResponse($response)
    {
        if (is_array($response)) {
            $PARSED = [];
            $CURRENT = null;
            $singlevalue = null;
            foreach ($response as $x) {
                if (in_array($x, ['!fatal', '!re', '!trap'])) {
                    if ($x == '!re') {
                        $CURRENT = &$PARSED[];
                    } else {
                        $CURRENT = &$PARSED[$x][];
                    }
                } elseif ($x != '!done') {
                    $MATCHES = [];
                    if (preg_match_all('/[^=]+/i', $x, $MATCHES)) {
                        if ($MATCHES[0][0] == 'ret') {
                            $singlevalue = $MATCHES[0][1];
                        }
                        $CURRENT[$MATCHES[0][0]] = (isset($MATCHES[0][1]) ? $MATCHES[0][1] : '');
                    }
                }
            }
            if (empty($PARSED) && $singlevalue !== null) {
                $PARSED = $singlevalue;
            }
            return $PARSED;
        }
        return [];
    }

    /**
     * Baca tepat $n byte dari soket. Mengembalikan null jika timeout/EOF/gagal (hindari loop fread tanpa progres).
     */
    protected function readBytes(int $n, float $deadline): ?string
    {
        if ($n <= 0) {
            return '';
        }
        $buf = '';
        while (strlen($buf) < $n) {
            if (microtime(true) > $deadline) {
                return null;
            }
            if (! is_resource($this->socket)) {
                return null;
            }
            $chunk = fread($this->socket, $n - strlen($buf));
            if ($chunk === false) {
                return null;
            }
            if ($chunk === '') {
                $meta = stream_get_meta_data($this->socket);
                if (($meta['timed_out'] ?? false) || ($meta['eof'] ?? false)) {
                    return null;
                }
                usleep(2000);

                continue;
            }
            $buf .= $chunk;
        }

        return $buf;
    }

    public function read($parse = true)
    {
        $RESPONSE = [];
        $receiveddone = false;
        $deadline = microtime(true) + max(5.0, (float) $this->readMaxSeconds);

        while (true) {
            if (microtime(true) > $deadline) {
                $this->debug('read() deadline exceeded');
                $this->connected = false;
                break;
            }
            if (! is_resource($this->socket)) {
                break;
            }

            $first = $this->readBytes(1, $deadline);
            if ($first === null || $first === '') {
                $this->connected = false;
                break;
            }
            $BYTE = ord($first);
            $LENGTH = 0;
            if ($BYTE & 128) {
                if (($BYTE & 192) == 128) {
                    $b = $this->readBytes(1, $deadline);
                    if ($b === null) {
                        $this->connected = false;
                        break;
                    }
                    $LENGTH = (($BYTE & 63) << 8) + ord($b);
                } elseif (($BYTE & 224) == 192) {
                    $b = $this->readBytes(2, $deadline);
                    if ($b === null || strlen($b) < 2) {
                        $this->connected = false;
                        break;
                    }
                    $LENGTH = (($BYTE & 31) << 8) + ord($b[0]);
                    $LENGTH = ($LENGTH << 8) + ord($b[1]);
                } elseif (($BYTE & 240) == 224) {
                    $b = $this->readBytes(3, $deadline);
                    if ($b === null || strlen($b) < 3) {
                        $this->connected = false;
                        break;
                    }
                    $LENGTH = (($BYTE & 15) << 8) + ord($b[0]);
                    $LENGTH = ($LENGTH << 8) + ord($b[1]);
                    $LENGTH = ($LENGTH << 8) + ord($b[2]);
                } else {
                    $b = $this->readBytes(4, $deadline);
                    if ($b === null || strlen($b) < 4) {
                        $this->connected = false;
                        break;
                    }
                    $LENGTH = ord($b[0]);
                    $LENGTH = ($LENGTH << 8) + ord($b[1]);
                    $LENGTH = ($LENGTH << 8) + ord($b[2]);
                    $LENGTH = ($LENGTH << 8) + ord($b[3]);
                }
            } else {
                $LENGTH = $BYTE;
            }
            $_ = '';
            if ($LENGTH > 0) {
                $payload = $this->readBytes($LENGTH, $deadline);
                if ($payload === null || strlen($payload) < $LENGTH) {
                    $this->debug('read() incomplete payload');
                    $this->connected = false;
                    break;
                }
                $_ = $payload;
                $RESPONSE[] = $_;
                $this->debug('>>> [' . strlen($payload) . '/' . $LENGTH . '] bytes read.');
            }
            if ($_ == '!done') {
                $receiveddone = true;
            }
            $meta = stream_get_meta_data($this->socket);
            $unread = isset($meta['unread_bytes']) ? $meta['unread_bytes'] : 0;
            if ((! $this->connected && $unread == 0) || ($this->connected && $receiveddone)) {
                break;
            }
        }
        if ($parse) {
            $RESPONSE = $this->parseResponse($RESPONSE);
        }

        return $RESPONSE;
    }

    public function write($command, $param2 = true)
    {
        if ($command) {
            $data = explode("\n", $command);
            foreach ($data as $com) {
                $com = trim($com);
                fwrite($this->socket, $this->encodeLength(strlen($com)) . $com);
                $this->debug('<<< [' . strlen($com) . '] ' . $com);
            }
            if (gettype($param2) == 'integer') {
                fwrite($this->socket, $this->encodeLength(strlen('.tag=' . $param2)) . '.tag=' . $param2 . chr(0));
            } elseif (gettype($param2) == 'boolean') {
                fwrite($this->socket, ($param2 ? chr(0) : ''));
            }
            return true;
        }
        return false;
    }

    public function comm($com, $arr = [])
    {
        $count = count($arr);
        $this->write($com, !$arr);
        $i = 0;
        if ($this->isIterable($arr)) {
            foreach ($arr as $k => $v) {
                switch ($k[0]) {
                    case '?':
                        $el = "$k=$v";
                        break;
                    case '~':
                        $el = "$k~$v";
                        break;
                    default:
                        $el = "=$k=$v";
                        break;
                }
                $last = ($i++ == $count - 1);
                $this->write($el, $last);
            }
        }
        return $this->read();
    }

    public function __destruct()
    {
        $this->disconnect();
    }
}
