<?php
class ControllerProductCompare extends Controller {
    public function index(): void {
        $this->load->language('product/compare');

        // Image files
        $this->load->model('tool/image');

        // Products
        $this->load->model('catalog/product');

        if (!isset($this->session->data['compare'])) {
            $this->session->data['compare'] = [];
        }

        if (isset($this->request->get['remove'])) {
            $key = array_search($this->request->get['remove'], $this->session->data['compare']);

            if ($key !== false) {
                unset($this->session->data['compare'][$key]);

                $this->session->data['success'] = $this->language->get('text_remove');
            }

            $this->response->redirect($this->url->link('product/compare'));
        }

        $this->document->setTitle($this->language->get('heading_title'));

        $data['breadcrumbs'] = [];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/home')
        ];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('product/compare')
        ];

        if (isset($this->session->data['success'])) {
            $data['success'] = $this->session->data['success'];

            unset($this->session->data['success']);
        } else {
            $data['success'] = '';
        }

        $data['review_status'] = $this->config->get('config_review_status');
        $data['products'] = [];
        $data['attribute_groups'] = [];

        foreach ((array)$this->session->data['compare'] as $key => $product_id) {
            $product_info = $this->model_catalog_product->getProduct($product_id);

            if ($product_info) {
                if ($product_info['image']) {
                    $image = $this->model_tool_image->resize($product_info['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_compare_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_compare_height'));
                } else {
                    $image = false;
                }

                if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
                    $price = $this->currency->format($this->tax->calculate($product_info['price'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
                } else {
                    $price = false;
                }

                if (!is_null($product_info['special']) && (float)$product_info['special'] >= 0) {
                    $special = $this->currency->format($this->tax->calculate($product_info['special'], $product_info['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
                } else {
                    $special = false;
                }

                if ($product_info['quantity'] <= 0) {
                    $availability = $product_info['stock_status'];
                } elseif ($this->config->get('config_stock_display')) {
                    $availability = $product_info['quantity'];
                } else {
                    $availability = $this->language->get('text_instock');
                }

                $attribute_data = [];

                $attribute_groups = $this->model_catalog_product->getProductAttributes($product_id);

                foreach ($attribute_groups as $attribute_group) {
                    foreach ($attribute_group['attribute'] as $attribute) {
                        $attribute_data[$attribute['attribute_id']] = $attribute['text'];
                    }
                }

                $data['products'][$product_id] = [
                    'product_id'   => $product_info['product_id'],
                    'name'         => $product_info['name'],
                    'thumb'        => $image,
                    'price'        => $price,
                    'special'      => $special,
                    'description'  => oc_substr(strip_tags(html_entity_decode($product_info['description'], ENT_QUOTES, 'UTF-8')), 0, 200) . '..',
                    'model'        => $product_info['model'],
                    'manufacturer' => $product_info['manufacturer'],
                    'availability' => $availability,
                    'minimum'      => $product_info['minimum'] > 0 ? $product_info['minimum'] : 1,
                    'rating'       => (int)$product_info['rating'],
                    'reviews'      => sprintf($this->language->get('text_reviews'), (int)$product_info['reviews']),
                    'weight'       => $this->weight->format($product_info['weight'], $product_info['weight_class_id'], $this->language->get('decimal_point'), $this->language->get('thousand_point')),
                    'length'       => $this->length->format($product_info['length'], $product_info['length_class_id'], $this->language->get('decimal_point'), $this->language->get('thousand_point')),
                    'width'        => $this->length->format($product_info['width'], $product_info['length_class_id'], $this->language->get('decimal_point'), $this->language->get('thousand_point')),
                    'height'       => $this->length->format($product_info['height'], $product_info['length_class_id'], $this->language->get('decimal_point'), $this->language->get('thousand_point')),
                    'attribute'    => $attribute_data,
                    'href'         => $this->url->link('product/product', 'product_id=' . $product_id),
                    'remove'       => $this->url->link('product/compare', 'remove=' . $product_id)
                ];

                foreach ($attribute_groups as $attribute_group) {
                    $data['attribute_groups'][$attribute_group['attribute_group_id']]['name'] = $attribute_group['name'];

                    foreach ($attribute_group['attribute'] as $attribute) {
                        $data['attribute_groups'][$attribute_group['attribute_group_id']]['attribute'][$attribute['attribute_id']]['name'] = $attribute['name'];
                    }
                }
            } else {
                unset($this->session->data['compare'][$key]);
            }
        }

        $data['continue'] = $this->url->link('common/home');

        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');

        $this->response->setOutput($this->load->view('product/compare', $data));
    }

    public function add(): void {
        $this->load->language('product/compare');

        $json = [];

        if (!isset($this->session->data['compare'])) {
            $this->session->data['compare'] = [];
        }

        if (isset($this->request->post['product_id'])) {
            $product_id = (int)$this->request->post['product_id'];
        } else {
            $product_id = 0;
        }

        // Products
        $this->load->model('catalog/product');

        $product_info = $this->model_catalog_product->getProduct($product_id);

        if ($product_info) {
            if (!in_array($this->request->post['product_id'], (array)$this->session->data['compare'])) {
                if (count($this->session->data['compare']) >= 4) {
                    array_shift($this->session->data['compare']);
                }

                $this->session->data['compare'][] = (int)$this->request->post['product_id'];
            }

            $json['success'] = sprintf($this->language->get('text_success'), $this->url->link('product/product', 'product_id=' . $this->request->post['product_id']), $product_info['name'], $this->url->link('product/compare'));
            $json['total'] = sprintf($this->language->get('text_compare'), isset($this->session->data['compare']) ? count($this->session->data['compare']) : 0);
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}