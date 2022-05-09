<?php if (!defined('ABSPATH')) exit; ?>

<?php if ($is_trial): ?>
  <div style="padding: 10px;border: 1px solid #f00; background-color: #fdd; color: #f00; margin: 10px 2px">
    <h3 style="color: #f00"><?php _e( 'MODO DE TESTES', VINDI ); ?></h3>
    <p>
      <?php _e('Sua conta na Vindi está em <strong>Modo Trial</strong>. Este modo é proposto para a realização de testes e, portanto, nenhum pedido será efetivamente cobrado.', VINDI); ?>
    </p>
  </div>
<?php endif; ?>

<fieldset class="vindi-fieldset">

  <?php do_action('vindi_credit_card_form_start', $id); ?>

  <div class="vindi-new-cc-data">
    <div class="vindi_cc_form-container">
      <div class="field-container">
        <label for="vindi_cc_name">
          <?php _e("Nome Impresso no Cartão", VINDI); ?>
          <span class="required">*</span>
        </label>
        <input id="vindi_cc_name" name="vindi_cc_fullname" maxlength="20" type="text">
      </div>
      <div class="field-container">
        <label for="vindi_cc_cardnumber">
          <?php _e("Número do Cartão", VINDI); ?>
          <span class="required">*</span>
        </label>
        <input id="vindi_cc_cardnumber" name="vindi_cc_number" type="text" pattern="[0-9]*" inputmode="numeric" autocomplete="off" placeholder="•••• •••• •••• ••••">
        <svg id="vindi_cc_ccicon" class="vindi_cc_ccicon" width="750" height="471" viewBox="0 0 750 471" version="1.1" xmlns="http://www.w3.org/2000/svg"
          xmlns:xlink="http://www.w3.org/1999/xlink">
        </svg>
      </div>
      <div class="field-container">
        <label for="vindi_cc_expirationdate">
          <?php _e("Validade (mm/aa)", VINDI) ?>
          <span class="required">*</span>
        </label>
        <input id="vindi_cc_expirationdate" name="vindi_cc_expirationdate" type="text" pattern="[0-9]*" inputmode="numeric" placeholder="mm/aa" autocomplete="off">
      </div>
      <div class="field-container">
        <label for="vindi_cc_securitycode">
          <?php _e("Código de Segurança", VINDI); ?>
          <span class="required">*</span>
        </label>
        <input id="vindi_cc_securitycode" name="vindi_cc_cvc" type="text" pattern="[0-9]*" inputmode="numeric" placeholder="CVC" autocomplete="off">
      </div>
      <div class="field-container">
        <label for="vindi_cc_paymentcompany">
            <?php _e("Bandeira do cartão", VINDI_IDENTIFIER); ?>
            <span class="required">*</span>
        </label>
        <select id="vindi_cc_paymentcompany" name="vindi_cc_paymentcompany" class="input-text" style="width: 100%">
          <option></option>
          <?php foreach($payment_methods['credit_card'] as $payment_company): ?>
            <option value="<?php echo $payment_company['code']; ?>"><?php echo $payment_company['name']; ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <input name="vindi_cc_monthexpiry" type="hidden">
      <input name="vindi_cc_yearexpiry" type="hidden">
    </div>
  </div>

  <?php if (isset($installments)): ?>
    <p class="form-row form-row-wide">
      <label for="vindi_cc_installments"><?php _e("Número de Parcelas", VINDI); ?>
        <span class="required">*</span>
      </label>
      <select name="vindi_cc_installments" class="input-text" style="width: 100%">
        <?php foreach($installments as $installment => $price): ?>
          <option value="<?php echo $installment; ?>"><?php echo sprintf(__('%dx de %s', VINDI), $installment, wc_price($price)); ?></option>
        <?php endforeach; ?>
      </select>
    </p>
  <?php endif; ?>
  <div class="clear"></div>

  <?php do_action('vindi_credit_card_form_end', $id); ?>

  <div class="clear"></div>
</fieldset>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.11/jquery.mask.min.js"></script>

<script type="text/javascript">
	$("#vindi_cc_cardnumber").on('change keydown paste input', function(){
		  validateCreditCardNumber();
	});

  var visaPattern = /^4\d{12}(\d{3})?$/;
  var masterPattern = /^(5[1-5]\d{4}|677189)\d{10}$/;
  var dinnersPattern = /^3(0[0-5]|[68]\d)\d{11}$/;
  var amexPattern = /^3[47]\d{13}$/;
  var discoveryPattern = /^6(?:011|5[0-9]{2})[0-9]{12}$/; 
  var jcbPattern = /^(?:2131|1800|35\d{3})\d{11}$/;
  var auraPattern = /^(5078\d{2})(\d{2})(\d{11})$/;
  var hipercardPattern = /^(606282\d{10}(\d{3})?)|(3841\d{15})$/;
  var eloPattern = /^(50(67(0[78]|1[5789]|2[012456789]|3[0123459]|4[0-7]|53|7[4-8])|9(0(0[0-9]|1[34]|2[0134567]|3[0359]|4[01235678]|5[015789]|6[012356789]|7[013]|8[1234789]|9[1379])|1(0[34568]|4[6-9]|5[1-8]|8[36789])|2(2[02]|5[7-9]|6[012356789]|7[012345689]|8[012356789]|90)|357|4(0[7-9]|1[0-9]|2[0-2]|5[7-9]|6[0-7]|8[45])|55[01]|636|7(2[3-8]|31|6[5-9])))|4(0117[89]|3(1274|8935)|5(1416|7(393|63[12])))|6(27780|36368|5(0(0(3[1258]|4[026]|7[78])|4(06|1[0234]|2[2-9]|3[04589]|8[5-9]|9[0-9])|5(0[01346789]|1[012456789]|2[0-9]|3[0178]|5[2-9]|6[0-6]|7[7-9]|8[0134678]|9[1-8])|72[0-7]|9(0[1-9]|1[0-8]|2[0128]|3[89]|4[6-9]|5[0158]|6[2-9]|7[01]))|16(5[236789]|6[025678]|7[01456789]|88)|50(0[01356789]|1[2568]|36|5[1267]))))$/;

  function validateCreditCardNumber() {
      var ccNum  = document.getElementById("vindi_cc_cardnumber").value.replace(/ /g,'');

      if (eloPattern.test( ccNum ) === true) {
          document.getElementById("vindi_cc_paymentcompany").value = "elo";
      } else if (hipercardPattern.test( ccNum ) === true) {
          document.getElementById("vindi_cc_paymentcompany").value = "hipercard";
      } else if (dinnersPattern.test( ccNum ) === true) {
          document.getElementById("vindi_cc_paymentcompany").value = "diners_club";
      } else if (amexPattern.test( ccNum ) === true) {
          document.getElementById("vindi_cc_paymentcompany").value = "american_express";
      } else if (masterPattern.test( ccNum ) === true) {
         document.getElementById("vindi_cc_paymentcompany").value = "mastercard";
      } else if (visaPattern.test( ccNum ) === true) {
          document.getElementById("vindi_cc_paymentcompany").value = "visa";
      } else if (discoveryPattern.test( ccNum ) === true) {
          document.getElementById("vindi_cc_paymentcompany").value = "discover";
      } else if (jcbPattern.test( ccNum ) === true) {
          document.getElementById("vindi_cc_paymentcompany").value = "jcb";
      } else {
          document.getElementById("vindi_cc_paymentcompany").value = "";
      } 
  }
</script>

