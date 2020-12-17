$(function()
{
    // 支付操作
    $('.submit-delivery').on('click', function()
    {
        $('form.delivery-form input[name=id]').val($(this).data('id'));
        var user_id = $(this).data('user-id') || 0;
        $('form.delivery-form input[name=user_id]').val(user_id);

        $('form.delivery-form input[name=express_number]').val('');
        var order_model = $(this).data('order-model') || 0;
        if(order_model == 2){
            $('div.express_list').hide();
        }else{
            $('div.express_list').show();
            var express_id = $(this).data('express-id') || 0;

            $('form.delivery-form input[name=express_id]').val(express_id);
            $('ul.express-list li.selected').removeClass('selected');
            if(express_id != 0) {
                $('.express-items-'+express_id).addClass('selected').siblings('li').removeClass('selected');
            }
        }
        $('form.delivery-form input[name=refund_id]').val(0);
        $('ul.refund_type li.selected').removeClass('selected');
        $('.refund-items-0').addClass('selected').siblings('li').removeClass('selected');

        var status = $(this).data('status');
        $('form.delivery-form input[name=order_status]').val(status);
        $('form.delivery-form input[name=order_model]').val(order_model);
        // ajax请求
        $.ajax({
            url:$(this).data('items-url'),
            type:'POST',
            dataType:"json",
            timeout:30000,
            data:{'order_id': $('form.delivery-form input[name=id]').val()},
            success:function(result)
            {
                if(result.code == 0)
                {
                    var json = result.data;
                    var tables = '<table id="delivery-table" class="am-table am-table-bordered am-table-centered am-table-compact am-margin-vertical-xs am-sm-only-text-justify data-list">' +
                            '<thead>' + 
                                '<tr>' + 
                                    '<th width="500px">name</th>' + 
                                    // '<th>spec</th>' + 
                                    '<th width="100px">price</th>' + 
                                    '<th width="50px">count</th>' + 
                                    '<th width="80px">discount_price</th>' + 
                                    '<th width="230px">barcode</th>' + 
                                    '<th width="80px">已退数</th>' + 
                                    '<th width="400px">退货数量</th>' +                         
                                '</div>' + 
                                '</tr>' + 
                            '</thead>' + 
                            '<tbody>';
                    var body = '';
                    for(var i in json){
                        body += '<tr>' +
                                '<td>' + json[i].title + '</td>' +
                                // '<td>' + (json[i].spec_text || '') + '</td>' +
                                '<td>' + json[i].price + '</td>' +
                                '<td>' + json[i].buy_number + '</td>' +
                                '<td>' + json[i].discount_price + '</td>' +
                                '<td>' + json[i].spec_barcode + '</td>' +
                                '<td>' + json[i].returned_quantity + '</td>' +
                                '<td><input type="hidden" class="detail_ids" value="' + json[i].id + '">' + 
                                '<input type="hidden" class="detail_buys" value="' + (json[i].buy_number - json[i].returned_quantity) + '">' + 
                                '<input type="number" class="detail_nums" placeholder="退货数量(0-' + 
                                (json[i].buy_number - json[i].returned_quantity) + 
                                ')" class="am-radius popup_all_number" min="0" max="' +
                                (json[i].buy_number - json[i].returned_quantity) + '" data-validation-message="退货数量" value="" /></td>' +
                            '</tr>';

                    }
                    $('.delivery-tables').html(tables + body + '</tbody></table>'); 
                } else {
                    Prompt(result.msg);
                }
            },
            error:function()
            {
                Prompt('服务器错误');
            }
        });                 

    });

    // 混合列表选择
    $('.business-item ul li').on('click', function()
    {
        if($(this).hasClass('selected'))
        {
            $('form input[name='+$(this).parent().data('type')+'_id]').val(0);
            $(this).removeClass('selected');
        } else {
            $('form input[name='+$(this).parent().data('type')+'_id]').val($(this).data('value'));
            $(this).addClass('selected').siblings('li').removeClass('selected');
        }
    });

    // 发货操作表单
    FromInit('form.form-validation-delivery');
    $('form.delivery-form button[type=submit]').on('click', function()
    {
        var id = $('form.delivery-form input[name=id]').val() || 0;
        if(id == 0)
        {
            Prompt('订单id有误');
            return false;
        }
        var status = $('form.delivery-form input[name=order_status]').val();
        var order_model = $('form.delivery-form input[name=order_model]').val() || 0;

        var deliver_numbers_json = {};
        $('#delivery-table tr:has(.detail_nums)').each(function(){
            var detail_nums = $('.detail_nums',this).val() || 0;
            var detail_ids = $('.detail_ids',this).val();
            var detail_buys = $('.detail_buys',this).val();
            if(detail_nums > detail_buys){
                Prompt('退货数量不能大于可退数量');
                return false;
            }
            if(detail_nums > 0){
                deliver_numbers_json[detail_ids] = detail_nums;
            }
        });
        
        $('form.delivery-form input[name=deliver_numbers]').val(JSON.stringify(deliver_numbers_json));
        if(status == 2 && order_model != 2){
            var express_id = $('form.delivery-form input[name=express_id]').val() || 0;
            if(express_id == 0)
            {
                Prompt('请选择快递方式');
                return false;
            }
        }

        if(status == 2){
            //发货
            $("form.delivery-form").attr("action", $('form.delivery-form input[name=status_url_2]').val());
        }else{
            //退货
            $("form.delivery-form").attr("action", $('form.delivery-form input[name=status_url_0]').val());
        }
        
    });


    // 支付操作
    $('.submit-pay').on('click', function()
    {
        $('form.pay-form input[name=id]').val($(this).data('id'));
        var payment_id = $(this).data('payment-id') || 0;
        if($('.payment-items-'+payment_id).length > 0)
        {
            $('form.pay-form input[name=payment_id]').val(payment_id);
            $('.payment-items-'+payment_id).addClass('selected').siblings('li').removeClass('selected');
        } else {
            $('form.pay-form input[name=payment_id]').val(0);
            $('ul.payment-list li.selected').removeClass('selected');
        }
    });

    // 支付操作表单
    FromInit('form.form-validation-pay');
    $('form.pay-form button[type=submit]').on('click', function()
    {
        var id = $('form.pay-form input[name=id]').val() || 0;
        if(id == 0)
        {
            PromptCenter('订单id有误');
            return false;
        }
        var payment_id = $('form.pay-form input[name=payment_id]').val() || 0;
        if(payment_id == 0)
        {
            PromptCenter('请选择支付方式');
            return false;
        }
    });

    // 取货操作
    $('.submit-take').on('click', function()
    {
        $('form.take-form input[name=id]').val($(this).data('id') || 0);
        $('form.take-form input[name=user_id]').val($(this).data('value') || 0);
        $('form.take-form input[name=value]').val($(this).data('value') || 0);
    });

    // 取货操作表单
    FromInit('form.form-validation-take');
    $('form.take-form button[type=submit]').on('click', function()
    {
        if(($('form.take-form input[name=id]').val() || 0) == 0)
        {
            Prompt('订单id有误');
            return false;
        }
    });

    // 取消并退款操作
    $('.submit-cancel-refund').on('click', function()
    {
        $('form.form-cancel-refund input[name=refund_id]').val(0);
        $('ul.cancel_refund_type li.selected').removeClass('selected');
        $('ul.cancel_refund_type li.refund-items-0').addClass('selected').siblings('li').removeClass('selected');
        $('form.form-cancel-refund input[name=id]').val($(this).data('id') || 0);
        $('form.form-cancel-refund input[name=user_id]').val($(this).data('value') || 0);
        $('form.form-cancel-refund input[name=value]').val($(this).data('value') || 0);
    });

    // 取消并退款操作表单
    FromInit('form.form-cancel-refund');
    $('form.form-cancel-refund button[type=submit]').on('click', function()
    {
        if(($('form.form-cancel-refund input[name=id]').val() || 0) == 0)
        {
            Prompt('订单id有误');
            return false;
        }
    });

});