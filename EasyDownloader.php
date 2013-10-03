<?php
/**
 * easy downloader
 *
 * download with speed limit and resume
 * best performance in download managers software
 *
 * @category: download
 * @license: http://www.php.net/license/3_01.txt
 * @author: saeed <sjlover6@gmail.com>
 * @version: 1.0.0
 * @copyright: copyright (c) 2013 saeed johary
 */
class EasyDownloader
{
    /**
     * site url
     *
     * @access public
     * @var string
     */
    public $siteUrl;

    /**
     * file name
     *
     * @access public
     * @var string
     */
    public $fileName;

    /**
     * file or folder path
     *
     * @access public
     * @var string
     */
    public $filePath;

    /**
     * download resume
     *
     * @access public
     * @var boolean
     */
    public $resume = TRUE;

    /**
     * download max speed
     *
     * @access public
     * @var integer
     */
    public $maxSpeed = 40;

    /**
     * max connection support from server
     *
     * @access public
     * @var integer
     */
    public $maxConnection = 9;

    /**
     * query string for file or folder path
     *
     * @access public
     * @var string
     */
    public $queryString = '?path=';

    /**
     * show directory header template
     *
     * @access public
     * @var string
     */
    public $headerTemplate = '<table>';

    /**
     * show directory content template
     *
     * @access public
     * @var string
     */
    public $bodyTemplate = '<tr><td>{name}</td><td>{MB}MB , {KB}KB</td></tr>';

    /**
     * show directory footer template
     *
     * @access public
     * @var string
     */
    public $footerTemplate = '</table>';

    /**
     * list of file mime types , this is not all mime types
     * if you need all mime types change this
     *
     * @access public
     * @var array
     */
    public $mimeTypes = array(

        // text mime type
        'txt'   =>   'text/plain',
        'css'   =>   'text/css',
        'xml'   =>   'application/xml',

        // image mime type
        'png'   =>   'image/png',
        'jpg'   =>   'image/jpeg',
        'jpeg'  =>   'image/jpeg',
        'gif'   =>   'image/gif',
        'bmp'   =>   'image/bmp',

        // archive mime type
        'zip'   =>   'application/zip',
        'rar'   =>   'application/rar',
        'exe'   =>   'application/x-msdownload',

        // audio mime type
        'flv'   =>   'audio/x-flv',
        'mp3'   =>   'audio/mpeg',
        'wav'   =>   'audio/wav',
        'avi'   =>   'audio/msvideo',
        'wmv'   =>   'audio/x-ms-wmv',
        '3gp'   =>   'audio/tgpp',

        // default mime type
        'default' => 'application/octet-stream',
    );

    /**
     * user authenticate for download after login
     *
     * @access private
     * @var boolean
     */
    private $_authenticate = FALSE;

    /**
     * start range for download resume
     *
     * @access private
     * @var integer
     */
    private $_startRange;

    /**
     * end range for download resume
     *
     * @access private
     * @var integer
     */
    private $_endRange;

    /**
     * class constructor , configuration
     *
     * @param array $config properties data
     * @access public
     */
    public function __construct( $config )
    {
        foreach( $config as $name => $value )
        {
            if( $name !== '_autnenticate' AND $name !== '_startRange' AND $name !== '_endRange')
            {
                $this->{$name} = $value;
            }
        }
    }

    /**
     * login to site and set user max speed , resume
     *
     * @param array $users users list
     * @access public
     * @return void
     */
    public function login( $users )
    {
        if( empty( $users ) )
        {
            exit('invalid argument in login method');
        }

        if( isset( $_SERVER['PHP_AUTH_USER'] ) AND isset( $_SERVER['PHP_AUTH_PW'] ) )
        {
            foreach( $users as $user => $info )
            {
                if( $info['username'] == $_SERVER['PHP_AUTH_USER'] AND $info['password'] == $_SERVER['PHP_AUTH_PW'])
                {
                    $this->maxSpeed = isset($info['maxSpeed']) ? $info['maxSpeed'] : $this->maxSpeed;
                    $this->resume = $info['resume'] ? true : false;
                    $this->_authenticate = true;
                }
            }
        }
        $this->_showLogin();
    }

    /**
     * show login form
     *
     * @access private
     * @return void
     */
    private function _showLogin()
    {
        if( !$this->_authenticate )
        {
            header('WWW-Authenticate: Basic realm=please enter your username and password');
            header('HTTP/1.0 401 Unauthorized');
            header('Status: 401 Unauthorized');
            exit;
        }
    }

    /**
     * start download and - if is folder filePath , show directory
     *
     * @access public
     * @return string
     */
    public function startDownload()
    {
        if( is_dir( $this->filePath ) )
        {
            $this->_showDirectory();
        }

        if( !is_file( $this->filePath ) )
        {
            exit('not found '.$this->filePath.'');
        }

        if( !is_readable( $this->filePath ) )
        {
            exit('not readable '.$this->filePath.'');
        }

        set_time_limit(0);

        $this->_sendHeaders();
        $this->_setRanges();

        $handle = fopen( $this->filePath,'rb' );

        if( $this->resume AND $this->_startRange )
        {
            fseek( $handle,$this->_startRange );
            sleep(1);
        }

        if( $this->resume )
        {
            $speed = ( $this->maxSpeed*1024 )/( $this->maxConnection );
        }
        else
        {
            $speed = ( $this->maxSpeed*1024 );
        }

        while( !feof( $handle ) AND !connection_aborted() AND connection_status()==0 )
        {
            echo fread( $handle,$speed );
            flush();
            sleep(1);
        }
        fclose( $handle );
    }

    /**
     * show directory file and folder
     *
     * @param string $path file path
     * @access public
     * @return void
     */
    private function _showDirectory( $path = NULL )
    {
        if( empty( $path ) )
        {
            $path = $this->filePath;
        }

        $handle = opendir( $path );

        echo $this->headerTemplate;

        while( $data = readdir( $handle ) )
        {
            if( $data !== '..' AND $data !== '.' )
            {
                $find = array('{MB}', '{KB}', '{name}');
                $slash = !is_file( $this->filePath.$data ) ? '/' : '';
                $name = '<a href='.$this->siteUrl.$this->queryString.$this->filePath.$data.$slash.'>'.$data.$slash.'</a>';

                $mb = $this->byteToMegaByte( $this->getFileSize( $this->filePath.$data ) );
                $kb = $this->byteToKiloByte( $this->getFileSize( $this->filePath.$data ) );
                
                echo str_replace( $find, array($mb,$kb,$name), $this->bodyTemplate );
            }
        }

        echo $this->footerTemplate;
        exit;
    }

    /**
     * send header for download setting
     *
     * @access private
     * @return void
     */
    private function _sendHeaders()
    {
        header('Content-Discription: File Transfer');
        header('Content-Type:'.$this->getMimeType());
        header('Content-Disposition: attachment; filename='.$this->fileName.basename( $this->filePath ));
        header('Content-Transfer-Encoding:binary');
        header('Cache-Control: no-cache');
        header('Pragma: no-cache');
        header('Expires:0');

        if( $this->resume )
        {
            header('Accept-Ranges: bytes');
            header('HTTP/1.0 206 Partial Content');
            header('Status: 206 Partial Content');
            header('Last-Modified:'.gmdate('D, d M Y H:i:s',$this->getFileTime()).' GMT');
            header('Content-Length:'.(!empty( $this->_startRange ) ? ( $this->_endRange-$this->_startRange ) : $this->getFileSize()));
            header('Content-Range:'.$this->_startRange.'-'.$this->_endRange.'/'.$this->getFileSize());
        }
        else
        {
            header('Content-Length:'.$this->getFileSize());
        }
    }

    /**
     * set startrange , endrange for download resume
     *
     * @access private
     * @return void
     */
    private function _setRanges()
    {
        if( isset( $_SERVER['HTTP_RANGE'] ) AND $this->resume )
        {
            $ranges = explode( '-', substr($_SERVER['HTTP_RANGE'],-6,6) );

            $this->_startRange = $ranges[0];
            $this->_endRange = $ranges[1];
        }
    }

    /**
     * get time from file
     *
     * @access public
     * @return integer
     */
    public function getFileTime()
    {
         if( $time = filemtime( $this->filePath ) )
         {
              return $time;
         }
         else
         {
              return time();
         }
    }

    /**
     * get file size by kilobyte
     *
     * @param string $fileUrl for get size from this
     * @access public
     * @return integer
     */
    public function getFileSize( $fileUrl = NULL )
    {
        if( $size = filesize( $fileUrl ? $fileUrl : $this->filePath ) )
        {
            return $size;
        }
        else
        {
            return 0;
        }
    }

    /**
     * change byte to kilobyte from byte
     *
     * @param integer $byte
     * @access public
     * @return void
     */
    public function byteToKiloByte( $byte )
    {
        return round($byte/1024);
    }

    /**
     * change kilobyte to megabyte from kilo byte
     *
     * @param integer $kiloByte
     * @access public
     * @return integer
     */
    public function kiloByteToMegaByte( $kiloByte )
    {
        return round($kiloByte/1024);
    }

    /**
     * change byte to megabyte from byte
     *
     * @param integer $byte
     * @access public
     * @return integer
     */
    public function byteToMegaByte( $byte )
    {
        return round($byte/1024/1024);
    }

    /**
     * get file mime type
     *
     * @access public
     * @return string
     */
    public function getMimeType()
    {
        $fileType = pathinfo( $this->filePath, PATHINFO_EXTENSION );

        if( array_key_exists( $fileType, $this->mimeTypes ) )
        {
            return $this->mimeTypes[$fileType];
        }
        else
        {
            return $this->mimeTypes['default'];
        }
    }
}
