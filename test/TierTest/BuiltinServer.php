<?php


namespace TierTest;

class BuiltinServer
{
    private $address;
    
    private $directory;

    private $lockFile;
    
    private $childPID;
    
    public function __construct($address, $directory)
    {
        $this->address = $address;
        $this->directory = $directory;
        $this->lockFile = $this->getLockFile();
    }
    
    private function getLockFile()
    {
        return sys_get_temp_dir().'/lockFile_'.strtr($this->address, '.:', '--').'.pid';
    }

    public function exec_timeout($cmd, $timeout, &$output = '')
    {
        $fdSpec = [
            0 => ['file', '/dev/null', 'r'], //nothing to send to child process
            1 => ['pipe', 'w'], //child process's stdout
            //2 => ['file', '/dev/null', 'a'], //don't care about child process stderr
            2 => ['pipe', 'w', 'a'], //don't care about child process stderr
        ];

        $pipes = [];
        $proc = proc_open($cmd, $fdSpec, $pipes);
//        $status = proc_get_status($proc);
//        var_dump($status);
        
        stream_set_blocking($pipes[1], false);
        
        $stop = time() + $timeout;
        while (true === true) {
            $in = [$pipes[1], $pipes[2]];
            $out = [];
            $err = [];
            //stream_select($in, $out, $err, min(1, $stop - time()));
            stream_select($in, $out, $err, 0, 200);
            

            if (count($in) !== 0) {
                foreach ($in as $socketToRead) {
                    while (feof($socketToRead) !== false) {
                        $output .= stream_get_contents($socketToRead);
                        continue;
                    }
                }
            }

            if ($stop <= time()) {
                break;
            }
            else if ($this->isLockFileStillValid() === false) {
                break;
            }
        }
        

        fclose($pipes[1]); //close process's stdout, since we're done with it
        
        fclose($pipes[2]); //close process's stderr, since we're done with it
        //var_dump($output);
        
        $status = proc_get_status($proc);
        if (intval($status['running']) !== 0) {
            proc_terminate($proc); //terminate, since close will block until the process exits itself
            //This is the child process - so just exit
            exit(0);
            return -1;
        }
        else {
            proc_close($proc);
            //This is the child process - so just exit
            exit(0);
            return $status['exitcode'];
        }
    }

    private function isLockFileStillValid()
    {
        if (file_exists($this->lockFile) === false) {
            return false;
        }

        return true;
    }
    
    public function startServer($address)
    {
        $pid = pcntl_fork();
        $this->childPID = $pid;

        if ($pid < 0) {
            echo 'Unable to start the server process.';
            return 1;
        }

        $command = sprintf(
            "php -S localhost:%s -t %s",
            $this->address,
            $this->directory
        );

        if ($pid > 0) {
            //printf('Web server listening on http://%s', $address);
            // PHP server takes a moment to get into a state to be able to accept
            // requests
            // TOOD - make test requests rather than just sleeping.
            sleep(1);
            return 0;
        }

        if (posix_setsid() < 0) {
            echo 'Unable to set the child process as session leader';
            return -1;
        }

        $lockFile = $this->getLockFile($address);
        touch($lockFile);
        $this->exec_timeout($command, 15, $output);

        return 1;
    }

    public function removeLockFile()
    {
        @unlink($this->lockFile);
    }
    
    public function waitForChildToClose()
    {
        $status = null;
        if (defined('WNOHANG') === false) {
            define('WNOHANG', 1);
        }
        
        $options = WNOHANG;
        
        for ($i=0; $i<10; $i++) {
            $info = pcntl_waitpid($this->childPID, $status, $options);
            //var_dump($info);
            if ($info === 0) {
                //echo "Child has exited\n";
                return;
            }
            if ($info === -1) {
                //echo "Child has already exited?\n";
                return;
            }
        }

        echo "Child maybe failed to exit. You might have a zombie server.";
    }
}
