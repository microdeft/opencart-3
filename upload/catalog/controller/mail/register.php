<?php
class ControllerMailRegister extends Controller {
	// catalog/model/account/customer/addCustomer/after
	public function index(string &$route, array &$args, mixed &$output): void {
		$this->load->language('mail/register');

		$store_name = html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8');

		$subject = sprintf($this->language->get('text_subject'), $store_name);

		$data['text_welcome'] = sprintf($this->language->get('text_welcome'), $store_name);

		$this->load->model('account/customer_group');

		if (isset($args[0]['customer_group_id'])) {
			$customer_group_id = $args[0]['customer_group_id'];
		} else {
			$customer_group_id = $this->config->get('config_customer_group_id');
		}

		$customer_group_info = $this->model_account_customer_group->getCustomerGroup($customer_group_id);

		if ($customer_group_info) {
			$data['approval'] = $customer_group_info['approval'];
		} else {
			$data['approval'] = '';
		}

		$data['login'] = $this->url->link('account/login', '', true);

		$data['store'] = $store_name;
		$data['store_url'] = $this->config->get('config_url');

		if ($this->config->get('config_mail_engine')) {
			$mail = new \Mail($this->config->get('config_mail_engine'));
			$mail->parameter = $this->config->get('config_mail_parameter');
			$mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
			$mail->smtp_username = $this->config->get('config_mail_smtp_username');
			$mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
			$mail->smtp_port = $this->config->get('config_mail_smtp_port');
			$mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');

			$mail->setTo($args[0]['email']);
			$mail->setFrom($this->config->get('config_email'));
			$mail->setSender($store_name);
			$mail->setSubject($subject);
			$mail->setHtml($this->load->view('mail/register', $data));
			$mail->send();
		}
	}
	
	// catalog/model/account/customer/addCustomer/after
	public function alert(string &$route, array &$args, mixed &$output): void {
		// Send to main admin email if new account email is enabled
		if (in_array('account', (array)$this->config->get('config_mail_alert'))) {
			$this->load->language('mail/register');

			$store_name = html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8');

			$subject = $this->language->get('text_new_customer');

			$data['firstname'] = $args[0]['firstname'];
			$data['lastname'] = $args[0]['lastname'];

			$data['login'] = $this->url->link('account/login', '', true);

			$this->load->model('account/customer_group');

			if (isset($args[0]['customer_group_id'])) {
				$customer_group_id = $args[0]['customer_group_id'];
			} else {
				$customer_group_id = $this->config->get('config_customer_group_id');
			}

			$customer_group_info = $this->model_account_customer_group->getCustomerGroup($customer_group_id);

			if ($customer_group_info) {
				$data['customer_group'] = $customer_group_info['name'];
			} else {
				$data['customer_group'] = '';
			}

			$data['email'] = $args[0]['email'];
			$data['telephone'] = $args[0]['telephone'];

			$data['store'] = $store_name;
			$data['store_url'] = $this->config->get('config_url');

			if ($this->config->get('config_mail_engine')) {
				$mail = new \Mail($this->config->get('config_mail_engine'));
				$mail->parameter = $this->config->get('config_mail_parameter');
				$mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
				$mail->smtp_username = $this->config->get('config_mail_smtp_username');
				$mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
				$mail->smtp_port = $this->config->get('config_mail_smtp_port');
				$mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');

				$mail->setTo($this->config->get('config_email'));
				$mail->setFrom($this->config->get('config_email'));
				$mail->setSender($store_name);
				$mail->setSubject($subject);
				$mail->setHtml($this->load->view('mail/register_alert', $data));
				$mail->send();

				// Send to additional alert emails if new account email is enabled
				$emails = explode(',', $this->config->get('config_mail_alert_email'));

				foreach ($emails as $email) {
					if (utf8_strlen($email) > 0 && filter_var($email, FILTER_VALIDATE_EMAIL)) {
						$mail->setTo(trim($email));
						$mail->send();
					}
				}
			}
		}
	}
}		