<?php

namespace Ecommage\ShopeePay\Helper;

use Ecommage\ShopeePay\Logger\Logger;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Phrase;

class Data extends AbstractHelper
{
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var Logger
     */
    protected $logger;
    /**
     * @var array
     */
    protected $transactionStatus
        = [
            2 => 'Transaction processing',
            3 => 'Transaction successful',
            4 => 'Transaction failed'
        ];
    protected $resultCode
        = [
            100 => 'Thanh toán thành công',
            201 => 'Thanh toán đã bị hủy/khách bấm hủy thanh toán trên SPP',
            202 => 'Thanh toán thất bại. Đối tác có thể sử dụng reference_id mới để thử lại chứ không dùng lại id cũ vì đơn này đã thất bại rồi.',
            203 => 'Thanh toán đang xử lý',
            204 => 'Đơn hàng hết hạn',
        ];
    /**
     * @var string[]
     */
    protected $errors
        = [
            -3   => 'Sorry, but something went wrong.',
            -2   => 'A server dropped the connection',
            -1   => 'A server error occurred',
            0    => 'Success',
            1    => 'Request parameters error, such as missing mandatory parameters',
            2    => 'Permission denied',
            4    => 'Merchant/store/transaction not found',
            5    => 'Payment processing, use Notify Transaction Status or Check Transaction Status for updated payment status',
            6    => 'The user making the payment has not completed wallet activation',
            7    => 'Expired',
            9    => 'User’s account is banned',
            11   => 'Duplicate request/transaction',
            15   => 'Payment amount exceeded limit',
            24   => 'User’s account is frozen',
            42   => 'Insufficient balance',
            101  => 'User wallet limit exceeded',
            102  => 'User wallet limit exceeded',
            103  => 'User exceeded daily payment limit Limit will reset the next day',
            104  => 'User wallet limit exceeded',
            105  => 'Authorisation code is invalid',
            121  => 'Client attempts to update completed transaction',
            126  => 'User transaction limit reached',
            140  => 'User wallet limit exceeded',
            150  => 'Active linking count threshold reached',
            301  => 'Invalid payment code or QR content',
            303  => 'Merchant is trying to make payment to their own user account',
            304  => 'Refund cannot be processed due to payment exceeding validity period',
            305  => 'Merchant invalid',
            452  => 'Unable to invalidate order as payment_ref_id cannot be found',
            601  => 'Request to refund a payment transaction does not meet rules. This error is also returned during refund block periods, set at 11.55pm to 3am by default. This timing is subject to changing during campaign periods or system maintenance.',
            602  => 'Request to refund a payment transaction is unsuccessful',
            627  => 'Invalid promo_ids',
            1001 => 'User is not allowed to make the transaction',
            1002 => 'This service is currently under maintenance',
        ];

    /**
     * Data constructor.
     *
     * @param StoreManagerInterface $storeManager
     * @param Context               $context
     * @param Logger                $logger
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        Context $context,
        Logger $logger
    ) {
        $this->logger       = $logger;
        $this->storeManager = $storeManager;
        parent::__construct($context);
    }

    /**
     * @param       $route
     * @param array $params
     *
     * @return string
     */
    public function getUrl($route, $params = [])
    {
        return $this->_getUrl($route, $params);
    }

    /**
     * @param $code
     *
     * @return Phrase
     */
    public function getMessage($code)
    {
        $error = $this->errors[$code] ?? 'Something went wrong';
        return new Phrase($error);
    }

    /**
     * @param $code
     *
     * @return Phrase
     */
    public function getTransactionStatus($code)
    {
        $text = $this->transactionStatus[$code] ?? 'Transaction failed';
        return new Phrase($text);
    }

    /**
     * @param $code
     *
     * @return Phrase
     */
    public function getResultCode($code)
    {
        $text = $this->resultCode[$code] ?? 'Payment failed';
        return new Phrase($text);
    }

    /**
     * @param $message
     *
     * @return $this
     */
    public function log($message)
    {
        $this->logger->info($message);
        return $this;
    }
}
