<?php

// {{{ SubjectTxt

/**
 * SubjectTxt�N���X
 */
class SubjectTxt
{
    // {{{ properties

    public $host;
    public $bbs;
    public $subject_url;
    public $subject_file;
    public $subject_lines;
    public $storage;

    // }}}
    // {{{ constructor

    /**
     * �R���X�g���N�^
     */
    public function __construct($host, $bbs)
    {
        $this->host = $host;
        $this->bbs =  $bbs;
        $this->storage = 'file';

        $this->subject_file = P2Util::datDirOfHostBbs($host, $bbs) . 'subject.txt';
        $this->subject_url = 'http://' . $host . '/' . $bbs . '/subject.txt';

        // ������΂�livedoor�ړ]�ɑΉ��B�Ǎ����livedoor�Ƃ���B
        $this->subject_url = P2Util::adjustHostJbbs($this->subject_url);

        // subject.txt���_�E�����[�h���Z�b�g����
        $this->dlAndSetSubject();
    }

    // }}}
    // {{{ dlAndSetSubject()

    /**
     * subject.txt���_�E�����[�h���Z�b�g����
     *
     * @return boolean �Z�b�g�ł���� true�A�ł��Ȃ���� false
     */
    public function dlAndSetSubject()
    {
        $cont = $this->downloadSubject();
        if ($this->setSubjectLines($cont)) {
            return true;
        } else {
            return false;
        }
    }

    // }}}
    // {{{ downloadSubject()

    /**
     * subject.txt���_�E�����[�h����
     *
     * @return string subject.txt �̒��g
     */
    public function downloadSubject()
    {
        global $_conf;

        if ($this->storage === 'file') {
            FileCtl::mkdirFor($this->subject_file); // �f�B���N�g����������΍��

            if (file_exists($this->subject_file)) {
                if (!empty($_REQUEST['norefresh']) || (empty($_REQUEST['refresh']) && isset($_REQUEST['word']))) {
                    return;    // �X�V���Ȃ��ꍇ�́A���̏�Ŕ����Ă��܂�
                } elseif (!empty($GLOBALS['expack.subject.multi-threaded-download.done'])) {
                    return;    // ����_�E�����[�h�ς̏ꍇ��������
                } elseif (empty($_POST['newthread']) and $this->isSubjectTxtFresh()) {
                    return;    // �V�K�X�����Ď��łȂ��A�X�V���V�����ꍇ��������
                }
                $modified = http_date(filemtime($this->subject_file));
            } else {
                $modified = false;
            }
        }

        // DL
        $params = array();
        $params['timeout'] = $_conf['http_conn_timeout'];
        $params['readTimeout'] = array($_conf['http_read_timeout'], 0);
        if ($_conf['proxy_use']) {
            $params['proxy_host'] = $_conf['proxy_host'];
            $params['proxy_port'] = $_conf['proxy_port'];
        }
        $req = new HTTP_Request($this->subject_url, $params);
        $modified && $req->addHeader("If-Modified-Since", $modified);
        $req->addHeader('User-Agent', "Monazilla/1.00 ({$_conf['p2ua']})");

        $response = $req->sendRequest();

        if (PEAR::isError($response)) {
            $error_msg = $response->getMessage();
        } else {
            $code = $req->getResponseCode();
            if ($code == 302) {
                // �z�X�g�̈ړ]��ǐ�
                $new_host = BbsMap::getCurrentHost($this->host, $this->bbs);
                if ($new_host != $this->host) {
                    $aNewSubjectTxt = new SubjectTxt($new_host, $this->bbs);
                    $body = $aNewSubjectTxt->downloadSubject();
                    return $body;
                }
            }
            if (!($code == 200 || $code == 206 || $code == 304)) {
                //var_dump($req->getResponseHeader());
                $error_msg = $code;
            }
        }

        if (isset($error_msg) && strlen($error_msg) > 0) {
            $url_t = P2Util::throughIme($this->subject_url);
            $info_msg_ht = "<p class=\"info-msg\">Error: {$error_msg}<br>";
            $info_msg_ht .= "rep2 info: <a href=\"{$url_t}\"{$_conf['ext_win_target_at']}>{$this->subject_url}</a> �ɐڑ��ł��܂���ł����B</p>";
            P2Util::pushInfoHtml($info_msg_ht);
            $body = '';
        } else {
            $body = $req->getResponseBody();
        }

        // �� DL�������� ���� �X�V����Ă�����
        if ($body && $code != "304") {

            // ������� or be.2ch.net �Ȃ�EUC��SJIS�ɕϊ�
            if (P2Util::isHostJbbsShitaraba($this->host) || P2Util::isHostBe2chNet($this->host)) {
                $body = mb_convert_encoding($body, 'CP932', 'CP51932');
            }

            if (FileCtl::file_write_contents($this->subject_file, $body) === false) {
                p2die('cannot write file');
            }
        } else {
            // touch���邱�ƂōX�V�C���^�[�o���������̂ŁA���΂炭�ă`�F�b�N����Ȃ��Ȃ�
            // �i�ύX���Ȃ��̂ɏC�����Ԃ��X�V����̂́A�����C���i�܂Ȃ����A�����ł͓��ɖ��Ȃ����낤�j
            if ($this->storage === 'file') {
                touch($this->subject_file);
            }
        }

        return $body;
    }

    // }}}
    // {{{ isSubjectTxtFresh()

    /**
     * subject.txt ���V�N�Ȃ� true ��Ԃ�
     *
     * @return boolean �V�N�Ȃ� true�B�����łȂ���� false�B
     */
    public function isSubjectTxtFresh()
    {
        global $_conf;

        // �L���b�V��������ꍇ
        if (file_exists($this->subject_file)) {
            // �L���b�V���̍X�V���w�莞�Ԉȓ��Ȃ�
            // clearstatcache();
            if (filemtime($this->subject_file) > time() - $_conf['sb_dl_interval']) {
                return true;
            }
        }

        return false;
    }

    // }}}
    // {{{ setSubjectLines()

    /**
     * subject.txt ��ǂݍ���
     *
     * ��������΁A$this->subject_lines ���Z�b�g�����
     *
     * @param string $cont ����� eashm �p�ɓn���Ă���B
     * @return boolean ���s����
     */
    public function setSubjectLines($cont = '')
    {
        $this->subject_lines = FileCtl::file_read_lines($this->subject_file);

        // JBBS@������΂Ȃ�d���X���^�C���폜����
        if (P2Util::isHostJbbsShitaraba($this->host)) {
            $this->subject_lines = array_unique($this->subject_lines);
        }

        if ($this->subject_lines) {
            return true;
        } else {
            return false;
        }
    }

    // }}}
}

// }}}

/*
 * Local Variables:
 * mode: php
 * coding: cp932
 * tab-width: 4
 * c-basic-offset: 4
 * indent-tabs-mode: nil
 * End:
 */
// vim: set syn=php fenc=cp932 ai et ts=4 sw=4 sts=4 fdm=marker: