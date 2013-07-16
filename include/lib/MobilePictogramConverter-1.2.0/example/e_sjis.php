<?php
ini_set('error_reporting', E_ALL);
require_once '../MobilePictogramConverter.php';

header('Content-Type: text/html; charset=Shift_JIS');

/* �o�C�i���R�[�h */
$str    = '��'.pack('H*', 'F659').'��'.pack('H*', 'F65A').'��'.pack('H*', 'F65B').'��'.pack('H*', 'F748').'��';
$option = MPC_FROM_OPTION_RAW;
/* Web���̓R�[�h */
//$str    = '��<img localsrc="1">��<img localsrc="2">��<img localsrc="3">��<img localsrc="4">��';
//$option = MPC_FROM_OPTION_WEB;
/* �摜 */
//$str    = '��<img src="../img/e/1.gif" alt="" border="0" />��<img src="../img/e/2.gif" alt="" border="0" />��<img src="../img/e/3.gif" alt="" border="0" />��<img src="../img/e/4.gif" alt="" border="0" />��';
//$option = MPC_FROM_OPTION_IMG;

$mpc =& MobilePictogramConverter::factory($str, MPC_FROM_EZWEB, MPC_FROM_CHARSET_SJIS, $option);
if (is_object($mpc) == false) {
	die($mpc);
}
$mpc->setImagePath('../img/');
?>
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=Shift_JIS">
<title>EZweb�G��������̕ϊ� (SJIS)</title>
</head>
<body>
<?php
/* ���[�U�[�G�[�W�F���g����̎����ϊ� */
echo 'Auto :'.$mpc->autoConvert(MPC_TO_CHARSET_UTF8).'<br />'."\r\n";
/* �G�����폜 */
echo 'Delete : '.$mpc->Except().'<br />'."\r\n";
/* ������Ɋ܂܂�Ă���G�����̐� */
echo 'Count : '.$mpc->Count().'<br />'."\r\n";

/* ���o�C���\���p�iWeb���̓R�[�h�j */
//echo 'EZweb : '   .$mpc->Convert(MPC_TO_EZWEB   , MPC_TO_OPTION_WEB).'<br />'."\r\n";
//echo 'FOMA : '    .$mpc->Convert(MPC_TO_FOMA    , MPC_TO_OPTION_WEB).'<br />'."\r\n";
//echo 'SoftBank : '.$mpc->Convert(MPC_TO_SOFTBANK, MPC_TO_OPTION_WEB).'<br />'."\r\n";

/* ���o�C���\���p�i�o�C�i���R�[�h�j */
//echo 'EZweb : '   .$mpc->Convert(MPC_TO_EZWEB   , MPC_TO_OPTION_RAW).'<br />'."\r\n";
//echo 'FOMA : '    .$mpc->Convert(MPC_TO_FOMA    , MPC_TO_OPTION_RAW).'<br />'."\r\n";
//echo 'SoftBank : '.$mpc->Convert(MPC_TO_SOFTBANK, MPC_TO_OPTION_RAW).'<br />'."\r\n";

/* PC�\���p�i�摜�j */
//echo 'EZweb : '   .$mpc->Convert(MPC_TO_EZWEB   , MPC_TO_OPTION_IMG).'<br />'."\r\n";
//echo 'FOMA : '    .$mpc->Convert(MPC_TO_FOMA    , MPC_TO_OPTION_IMG).'<br />'."\r\n";
//echo 'SoftBank : '.$mpc->Convert(MPC_TO_SOFTBANK, MPC_TO_OPTION_IMG).'<br />'."\r\n";
?>
</body>
</html>