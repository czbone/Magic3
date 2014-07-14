/*
 * BOOTSTRAP�^�e���v���[�g�̏ꍇ�̋N�������sJava�X�N���v�g
 *
 * LICENSE: This source file is licensed under the terms of the GNU General Public License.
 *
 * @package    Magic3 Framework
 * @author     ���c���B(Naoki Hirata) <naoki@aplo.co.jp>
 * @copyright  Copyright 2006-2014 Magic3 Project.
 * @license    http://www.gnu.org/copyleft/gpl.html  GPL License
 * @version    SVN: $Id$
 * @link       http://www.magic3.org
 */
<patTemplate:tmpl name="_tmpl">
$(function(){
	// �A�b�v���[�h�t�@�C���I��
	$('.btn-file :file').on('fileselect', function(event, numFiles, label){
		var input = $(this).parents('.input-group').find(':text'),
			log = numFiles > 1 ? numFiles + ' files selected' : label;
		
		if (input.length){
			input.val(log);
		} else {
			if (log) alert(log);
		}
	});
	
	$(document).on('change', '.btn-file :file', function(){
	    var input = $(this),
	        numFiles = input.get(0).files ? input.get(0).files.length : 1,
	        label = input.val().replace(/\\/g, '/').replace(/.*\//, '');
	    input.trigger('fileselect', [numFiles, label]);
	});
});
</patTemplate:tmpl>
