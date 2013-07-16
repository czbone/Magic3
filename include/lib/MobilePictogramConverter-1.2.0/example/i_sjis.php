<?php
ini_set('error_reporting', E_ALL);
require_once '../MobilePictogramConverter.php';

header('Content-Type: text/html; charset=Shift_JIS');

/* �o�C�i���R�[�h */
$str    = '������������������������';
$option = MPC_FROM_OPTION_RAW;
/* Web���̓R�[�h */
//$str    = "��&#63647;&#63648;��&#63649;&#63650;��&#63651;&#xE70C;��&#xE70D;&#xE70E;��&#xE70F;&#xE710;";
//$option = MPC_FROM_OPTION_WEB;
/* �摜 */
#$str    = '��w<img src="../img/i/63647.gif" alt="" border="0" width="12" height="12" /><img src="../img/i/63648.gif" alt="" border="0" width="12" height="12" />��<img src="../img/i/63649.gif" alt="" border="0" width="12" height="12" /><img src="../img/i/63650.gif" alt="" border="0" width="12" height="12" />��<img src="../img/i/63651.gif" alt="" border="0" width="12" height="12" /><img src="../img/i/63921.gif" alt="" border="0" width="12" height="12" />��<img src="../img/i/63922.gif" alt="" border="0" width="12" height="12" /><img src="../img/i/63923.gif" alt="" border="0" width="12" height="12" />��<img src="../img/i/63924.gif" alt="" border="0" width="12" height="12" /><img src="../img/i/63925.gif" alt="" border="0" width="12" height="12" />';
#$option = MPC_FROM_OPTION_IMG;

$mpc =& MobilePictogramConverter::factory($str, MPC_FROM_FOMA, MPC_FROM_CHARSET_SJIS, $option);
if (is_object($mpc) == false) {
	die($mpc);
}
$mpc->setImagePath("../img/");
?>
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=Shift_JIS">
<title>i-mode�G��������̕ϊ� (SJIS)</title>
</head>
<body>
<?php
/* ���[�U�[�G�[�W�F���g����̎����ϊ� */
echo "Auto :".$mpc->autoConvert()."<br />\r\n";
/* �G�����폜 */
echo 'Delete : '.$mpc->Except().'<br />'."\r\n";
/* ������Ɋ܂܂�Ă���G�����̐� */
echo 'Count : '.$mpc->Count().'<br />'."\r\n";

/* ���o�C���\���p�iWeb���̓R�[�h�j */
//echo 'FOMA : '    .$mpc->Convert(MPC_TO_FOMA    , MPC_TO_OPTION_WEB).'<br />'."\r\n";
//echo 'EZweb : '   .$mpc->Convert(MPC_TO_EZWEB   , MPC_TO_OPTION_WEB).'<br />'."\r\n";
//echo 'SoftBank : '.$mpc->Convert(MPC_TO_SOFTBANK, MPC_TO_OPTION_WEB).'<br />'."\r\n";

/* ���o�C���\���p�i�o�C�i���R�[�h�j */
//echo 'FOMA : '    .$mpc->Convert(MPC_TO_FOMA    , MPC_TO_OPTION_RAW).'<br />'."\r\n";
//echo 'EZweb : '   .$mpc->Convert(MPC_TO_EZWEB   , MPC_TO_OPTION_RAW).'<br />'."\r\n";
//echo 'SoftBank : '.$mpc->Convert(MPC_TO_SOFTBANK, MPC_TO_OPTION_RAW).'<br />'."\r\n";

/* PC�\���p�i�摜�j */
//echo 'FOMA : '    .$mpc->Convert(MPC_TO_FOMA    , MPC_TO_OPTION_IMG).'<br />'."\r\n";
//echo 'EZweb : '   .$mpc->Convert(MPC_TO_EZWEB   , MPC_TO_OPTION_IMG).'<br />'."\r\n";
//echo 'SoftBank : '.$mpc->Convert(MPC_TO_SOFTBANK, MPC_TO_OPTION_IMG).'<br />'."\r\n";
?>
</body>
</html>