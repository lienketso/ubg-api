<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewOrders extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */

    public $email;
    public $product_list;
    public $customer_name;
    public $customer_phone;
    public $customer_address;
    public $shipping_method;
    public $payment_method;
    public $created_order;
    public $total_amount;
    public $shipping_amount;
    public $discount_amount;

    public function __construct(
                                $product_list,
                                $customer_name,
                                $customer_phone,
                                $customer_address,
                                $shipping_method,
                                $payment_method,
                                $created_order,
                                $total_amount,
                                $shipping_amount,
                                $discount_amount
    )
    {
        $this->product_list = $product_list;
        $this->customer_name = $customer_name;
        $this->customer_address = $customer_address;
        $this->customer_phone = $customer_phone;
        $this->shipping_method = $shipping_method;
        $this->payment_method = $payment_method;
        $this->created_order = $created_order;
        $this->total_amount = $total_amount;
        $this->shipping_amount = $shipping_amount;
        $this->discount_amount = $discount_amount;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this
            ->from('support@ubgmart.com','Ubgmart 4.0')
            ->subject('Thông báo đơn hàng từ App Ubgmart 4.0')
            ->markdown('emails.neworders');
    }
}
