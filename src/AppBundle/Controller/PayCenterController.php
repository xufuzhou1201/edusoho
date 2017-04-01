<?php

namespace AppBundle\Controller;

use Biz\Cash\Service\CashOrdersService;
use Biz\Coupon\Service\CouponService;
use Biz\Order\Service\OrderService;
use Biz\PayCenter\Service\PayCenterService;
use Biz\System\Service\LogService;
use Biz\User\Service\AuthService;
use AppBundle\Common\ArrayToolkit;
use AppBundle\Component\Payment\Payment;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Biz\Order\OrderProcessor\OrderProcessorFactory;

class PayCenterController extends BaseController
{
    public function showAction(Request $request)
    {
        $fields = $request->query->all();
        $orderInfo['sn'] = $fields['sn'];
        $orderInfo['targetType'] = $fields['targetType'];
        $orderInfo['isMobile'] = $this->isMobileClient();
        $processor = OrderProcessorFactory::create($fields['targetType']);
        $orderInfo['template'] = $processor->getOrderInfoTemplate();
        $order = $processor->getOrderBySn($orderInfo['sn']);

        $orderInfo['order'] = $order;
        $orderInfo['payments'] = $this->getEnabledPayments();
        $orderInfo['payAgreements'] = $this->getUserService()
            ->findUserPayAgreementsByUserId($this->getCurrentUser()->getId());

        foreach ($orderInfo['payments'] as $payment) {
            if ($payment['enabled']) {
                $orderInfo['firstEnabledPayment'] = $payment;
                break;
            }
        }

        return $this->render('pay-center/show.html.twig', $orderInfo);
    }

    public function redirectOrderTarget($order)
    {
        $processor = OrderProcessorFactory::create($order['targetType']);
        $goto = $processor->callbackUrl($order, $this->container);

        return $this->render('pay-center/pay-return.html.twig', array(
            'goto' => $goto,
        ));
    }

    public function payAction(Request $request)
    {
        $orderId = $request->request->get('orderId');
        $payment = $request->request->get('payment');

        list($checkResult, $order) = $this->getPayCenterGatewayService()->beforePayOrder($orderId, $payment);

        if ($checkResult) {
            return $this->createMessageResponse('error', $checkResult['error']);
        }

        if ($order['status'] == 'paid') {
            return $this->redirectOrderTarget($order);
        } else {
            return $this->forward('AppBundle:PayCenter:submitPayRequest', array(
                'order' => $order,
            ));
        }
    }

    public function submitPayRequestAction(Request $request, $order)
    {
        if (empty($order['payment'])) {
            return $this->createMessageResponse('error', '请选择支付方式');
        }

        $requestParams = array(
            'returnUrl' => $this->generateUrl('pay_return', array('name' => $order['payment']), true),
            'notifyUrl' => $this->generateUrl('pay_notify', array('name' => $order['payment']), true),
            'showUrl' => $this->generateUrl('pay_success_show', array('id' => $order['id']), true),
            'backUrl' => $this->generateUrl('pay_center_show', array('sn' => $order['sn'], 'targetType' => $order['targetType']), true),
        );
        $payment = $request->request->get('payment');

        if ($payment == 'quickpay') {
            $authBank = array();

            $payAgreementId = $request->request->get('payAgreementId', '');

            if (!empty($payAgreementId)) {
                $authBank = $this->getUserService()->getUserPayAgreement($payAgreementId);
            }

            $requestParams['authBank'] = $authBank;
        }

        $requestParams['userAgent'] = $request->headers->get('User-Agent');
        $requestParams['isMobile'] = $this->isMobileClient();

        $paymentRequest = $this->createPaymentRequest($order, $requestParams);
        $formRequest = $paymentRequest->form();
        $params = $formRequest['params'];

        if ($payment == 'wxpay') {
            $returnXml = $paymentRequest->unifiedOrder();

            if (!$returnXml) {
                throw new \RuntimeException('xml数据异常！');
            }

            $returnArray = $paymentRequest->fromXml($returnXml);

            if ($returnArray['return_code'] == 'SUCCESS') {
                $url = $returnArray['code_url'];

                return $this->render('pay-center/wxpay-qrcode.html.twig', array(
                    'url' => $url,
                    'order' => $order,
                ));
            } else {
                throw new \RuntimeException($returnArray['return_msg']);
            }
        } elseif ($payment == 'heepay' || $payment == 'quickpay') {
            $order = $this->generateOrderToken($order, $params);
        }

        return $this->render('pay-center/submit-pay-request.html.twig', array(
            'form' => $formRequest,
            'order' => $order,
        ));
    }

    public function unbindAuthAction(Request $request)
    {
        $this->getLogService()->info('order', 'unbind-back', '银行卡解绑');
        $fields = $request->request->all();
        $response = $this->verification($fields);

        if ($response) {
            return $this->createJsonResponse($response);
        }

        $authBank = $this->getUserService()->getUserPayAgreement($fields['payAgreementId']);
        $requestParams = array('authBank' => $authBank, 'payment' => 'quickpay', 'mobile' => $fields['mobile']);
        $unbindAuthBankRequest = $this->createUnbindAuthBankRequest($requestParams);
        $formRequest = $unbindAuthBankRequest->form();

        return $this->createJsonResponse($formRequest);
    }

    public function showMobileAction(Request $request)
    {
        $fields = $request->request->all();
        $response = $this->verification($fields);

        if ($response) {
            return $this->createJsonResponse($response);
        }

        return $this->render('pay-center/show-mobile.html.twig', array(
            'payAgreementId' => $fields['payAgreementId'],
        ));
    }

    public function payReturnAction(Request $request, $name, $successCallback = null)
    {
        if ($name == 'llpay') {
            $returnArray = $request->request->all();
            $returnArray['isMobile'] = $this->isMobileClient();
        } else {
            $returnArray = $request->query->all();
        }

        $this->getLogService()->info('order', 'pay_result', "{$name}页面跳转支付通知", $returnArray);
        $response = $this->createPaymentResponse($name, $returnArray);
        $payData = $response->getPayData();

        if ($payData['status'] == 'waitBuyerConfirmGoods') {
            return $this->forward('AppBundle:PayCenter:resultNotice');
        }

        if ($payData['status'] == 'insufficient balance') {
            return $this->createMessageResponse('error', '由于余额不足，支付失败，请重新支付。', null, 3000, $this->generateUrl('homepage'));
        }

        if (stripos($payData['sn'], 'o') !== false) {
            $order = $this->getCashOrdersService()->getOrderBySn($payData['sn']);
        } else {
            $order = $this->getOrderService()->getOrderBySn($payData['sn']);
        }
        list($success, $order) = OrderProcessorFactory::create($order['targetType'])->pay($payData);

        if (!$success) {
            return $this->redirect($this->generateUrl('pay_error'));
        }

        $processor = OrderProcessorFactory::create($order['targetType']);

        $goto = $processor->callbackUrl($order, $this->container);

        return $this->render('pay-center/pay-return.html.twig', array(
            'goto' => $goto,
        ));
    }

    public function payErrorAction(Request $request)
    {
        return $this->createMessageResponse('error', '由于余额不足，支付失败，订单已被取消。');
    }

    public function payNotifyAction(Request $request, $name)
    {
        if ($name == 'wxpay') {
            $returnXml = $request->getContent();
            $returnArray = $this->fromXml($returnXml);
        } elseif ($name == 'heepay' || $name == 'quickpay') {
            $returnArray = $request->query->all();
        } elseif ($name == 'llpay') {
            $returnArray = json_decode(file_get_contents('php://input'), true);
            $returnArray['isMobile'] = $this->isMobileClient();
        } else {
            $returnArray = $request->request->all();
        }

        $this->getLogService()->info('order', 'pay_result', "{$name}服务器端支付通知", $returnArray);

        $response = $this->createPaymentResponse($name, $returnArray);

        $payData = $response->getPayData();
        if ($payData['status'] == 'waitBuyerConfirmGoods') {
            return new Response('success');
        }

        if (stripos($payData['sn'], 'o') !== false) {
            $order = $this->getCashOrdersService()->getOrderBySn($payData['sn']);
        } else {
            $order = $this->getOrderService()->getOrderBySn($payData['sn']);
        }

        $processor = OrderProcessorFactory::create($order['targetType']);

        if ($payData['status'] == 'success') {
            list($success, $order) = $processor->pay($payData);

            if ($success) {
                if ($name == 'llpay') {
                    return new Response("{'ret_code':'0000','ret_msg':'交易成功'}");
                } else {
                    return new Response('success');
                }
            }
        }

        if ($payData['status'] == 'closed') {
            $order = $processor->getOrderBySn($payData['sn']);
            $processor->cancelOrder($order['id'], $name.'交易订单已关闭', $payData);

            return new Response('success');
        }

        if ($payData['status'] == 'created') {
            $order = $processor->getOrderBySn($payData['sn']);
            $processor->createPayRecord($order['id'], $payData);

            return new Response('success');
        }

        return new Response('failture');
    }

    public function showTargetAction(Request $request)
    {
        $orderId = $request->query->get('id');
        $order = $this->getOrderService()->getOrder($orderId);

        $processor = OrderProcessorFactory::create($order['targetType']);
        $router = $processor->callbackUrl($order, $this->container);

        return $this->render('pay-center/pay-return.html.twig', array(
            'goto' => $router,
        ));
    }

    public function payPasswordCheckAction(Request $request)
    {
        $password = $request->query->get('value');

        $user = $this->getCurrentUser();

        if (!$user->isLogin()) {
            $response = array('success' => false, 'message' => '用户未登录');
        }

        $isRight = $this->getAuthService()->checkPayPassword($user['id'], $password);

        if (!$isRight) {
            $response = array('success' => false, 'message' => '支付密码不正确');
        } else {
            $response = array('success' => true, 'message' => '支付密码正确');
        }

        return $this->createJsonResponse($response);
    }

    public function wxpayRollAction(Request $request)
    {
        $order = $request->query->get('order');

        if ($order['status'] == 'paid') {
            return $this->createJsonResponse(true);
        } else {
            $paymentRequest = $this->createPaymentRequest($order, array(
                'returnUrl' => '',
                'notifyUrl' => '',
                'showUrl' => '',
            ));
            $returnXml = $paymentRequest->orderQuery();
            $returnArray = $this->fromXml($returnXml);

            if ($returnArray['trade_state'] == 'SUCCESS') {
                $payData = array();
                $payData['status'] = 'success';
                $payData['payment'] = 'wxpay';
                $payData['amount'] = $order['amount'];
                $payData['paidTime'] = time();
                $payData['sn'] = $returnArray['out_trade_no'];

                list($success, $order) = OrderProcessorFactory::create($order['targetType'])->pay($payData);

                if ($success) {
                    return $this->createJsonResponse(true);
                }
            }
        }

        return $this->createJsonResponse(false);
    }

    public function resultNoticeAction(Request $request)
    {
        return $this->render('pay-center/result-notice.html.twig');
    }

    protected function createPaymentRequest($order, $requestParams)
    {
        $options = $this->getPaymentOptions($order['payment']);
        $request = Payment::createRequest($order['payment'], $options);
        $processor = OrderProcessorFactory::create($order['targetType']);
        $targetId = isset($order['targetId']) ? $order['targetId'] : $order['id'];
        $requestParams = array_merge($requestParams, array(
            'orderSn' => $order['sn'],
            'userId' => $order['userId'],
            'title' => $order['title'],
            'targetTitle' => $processor->getTitle($targetId),
            'summary' => '',
            'note' => $processor->getNote($targetId),
            'amount' => $order['amount'],
            'targetType' => $order['targetType'],
        ));

        return $request->setParams($requestParams);
    }

    protected function createUnbindAuthBankRequest($params)
    {
        $options = $this->getPaymentOptions($params['payment']);
        $request = Payment::createUnbindAuthRequest($params['payment'], $options);

        return $request->setParams(array('authBank' => $params['authBank'], 'mobile' => $params['mobile']));
    }

    public function generateOrderToken($order, $params)
    {
        $processor = OrderProcessorFactory::create($order['targetType']);

        return $processor->updateOrder($order['id'], array('token' => $params['agent_bill_id']));
    }

    public function verification($fields)
    {
        $user = $this->getCurrentUser();

        if (!$user->isLogin()) {
            return array('success' => false, 'message' => '用户未登录');
        }

        $authBanks = $this->getUserService()->findUserPayAgreementsByUserId($user['id']);
        $authBanks = ArrayToolkit::column($authBanks, 'id');

        if (!in_array($fields['payAgreementId'], $authBanks)) {
            return array('success' => false, 'message' => '不是您绑定的银行卡');
        }
    }

    protected function getPaymentOptions($payment)
    {
        $settings = $this->setting('payment');

        if (empty($settings)) {
            throw new \RuntimeException('支付参数尚未配置，请先配置。');
        }

        if (empty($settings['enabled'])) {
            throw new \RuntimeException('支付模块未开启，请先开启。');
        }

        if (empty($settings[$payment.'_enabled'])) {
            throw new \RuntimeException('支付模块('.$payment.')未开启，请先开启。');
        }

        if (empty($settings["{$payment}_key"]) || empty($settings["{$payment}_secret"])) {
            throw new \RuntimeException('支付模块('.$payment.')参数未设置，请先设置。');
        }

        if ($payment == 'alipay') {
            $options = array(
                'key' => $settings["{$payment}_key"],
                'secret' => $settings["{$payment}_secret"],
                'type' => $settings["{$payment}_type"],
            );
        } elseif ($payment == 'quickpay') {
            $options = array(
                'key' => $settings["{$payment}_key"],
                'secret' => $settings["{$payment}_secret"],
                'aes' => $settings["{$payment}_aes"],
            );
        } else {
            $options = array(
                'key' => $settings["{$payment}_key"],
                'secret' => $settings["{$payment}_secret"],
            );
        }

        return $options;
    }

    protected function createPaymentResponse($name, $params)
    {
        $options = $this->getPaymentOptions($name);
        $response = Payment::createResponse($name, $options);

        return $response->setParams($params);
    }

    private function fromXml($xml)
    {
        $array = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);

        return $array;
    }

    protected function getEnabledPayments()
    {
        $enableds = array();

        $setting = $this->setting('payment', array());
        if (empty($setting['enabled'])) {
            return $enableds;
        }

        $payment = $this->get('codeages_plugin.dict_twig_extension')->getDict('payment');
        $payNames = array_keys($payment);
        foreach ($payNames as $key => $payName) {
            if (!empty($setting[$payName.'_enabled'])) {
                $enableds[$key] = array(
                    'name' => $payName,
                    'enabled' => $setting[$payName.'_enabled'],
                );

                if ($this->isWxClient() && $payName == 'alipay') {
                    $enableds[$key]['enabled'] = 0;
                }

                if ($this->isMobileClient() && $payName == 'heepay') {
                    $enableds[$key]['enabled'] = 0;
                }

                if ($this->isMobileClient() && $payName == 'llcbpay') {
                    $enableds[$key]['enabled'] = 0;
                }
            }
        }

        return $enableds;
    }

    /**
     * @return AuthService
     */
    protected function getAuthService()
    {
        return $this->getBiz()->service('User:AuthService');
    }

    /**
     * @return OrderService
     */
    protected function getOrderService()
    {
        return $this->getBiz()->service('Order:OrderService');
    }

    /**
     * @return PayCenterService
     */
    protected function getPayCenterService()
    {
        return $this->getBiz()->service('PayCenter:PayCenterService');
    }

    /**
     * @return CashOrdersService
     */
    protected function getCashOrdersService()
    {
        return $this->getBiz()->service('Cash:CashOrdersService');
    }

    /**
     * @return LogService
     */
    protected function getLogService()
    {
        return $this->getBiz()->service('System:LogService');
    }

    protected function getPayCenterGatewayService()
    {
        return $this->createService('PayCenter:GatewayService');
    }
}
