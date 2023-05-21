<?php

namespace Ecommage\CustomCustomerLogin\Plugin\Customer\Controller\Account;

use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Customer\CredentialsValidator;
use Magento\Customer\Model\Session;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\InputException;

class ResetPasswordPost
{
    /**
     * @var \Magento\Customer\Api\AccountManagementInterface
     */
    protected $accountManagement;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var Session
     */
    protected $session;

    public function __construct
    (
        \Magento\Framework\Controller\ResultFactory $resultFactory,
        Session $customerSession,
        AccountManagementInterface $accountManagement,
        CustomerRepositoryInterface $customerRepository,
        CredentialsValidator $credentialsValidator = null
    )
    {
        $this->resultFactory = $resultFactory;
        $this->session = $customerSession;
        $this->accountManagement = $accountManagement;
        $this->customerRepository = $customerRepository;
    }

    public function aroundExecute(\Magento\Customer\Controller\Account\ResetPasswordPost $subject, callable $proceed)
    {
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        $resetPasswordToken = (string)$subject->getRequest()->getQuery('token');
        $customerId = (string)$subject->getRequest()->getQuery('id');
        $password = (string)$subject->getRequest()->getPost('password');
        $passwordConfirmation = (string)$subject->getRequest()->getPost('password_confirmation');
        $email = null;

        if ($password !== $passwordConfirmation) {
            $resultRedirect->setData(['status'=> 'error','message'=> __("New Password and Confirm New Password values didn't match.")]);

            return $resultRedirect;
        }
        if (iconv_strlen($password) <= 0) {
            $resultRedirect->setData(['status'=> 'error','message'=> __("Please enter a new password.")]);

            return $resultRedirect;
        }

        if ($customerId && $this->customerRepository->getById($customerId)) {
            $email = $this->customerRepository->getById($customerId)->getEmail();
        }

        try {
            $this->accountManagement->resetPassword(
                $email,
                $resetPasswordToken,
                $password
            );
            // logout from current session if password changed.
            if ($this->session->isLoggedIn()) {
                $this->session->logout();
                $this->session->start();
            }
            $this->session->unsRpToken();
            $this->session->unsRpCustomerId();
            $resultRedirect->setData(['status'=> 'success']);
        } catch (InputException $e) {
            foreach ($e->getErrors() as $error) {
                $resultRedirect->setData(['status'=> 'success','message'=> $error->getMessage()]);
            }
        } catch (\Exception $exception) {
            $resultRedirect->setData(['status'=> 'error','message'=> $exception->getMessage()]);
        }

        return $resultRedirect;
    }
}