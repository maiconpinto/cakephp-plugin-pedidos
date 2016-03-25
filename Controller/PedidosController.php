<?php

class PedidosController extends ComercialAppController
{
    public $components = array('Comercial.Utils');
    
    public $helpers = array('Comercial.Utils');

    private $referer = array('plugin' => 'comercial', 'admin' => false, 'controller' => 'comercial', 'action' => 'index');

    public function novo() 
    {
        if ($this->request->is('post')) {
            $this->Pedido->create();

            if ($this->Pedido->save($this->request->data)) {
                $this->Flash->success('Informe os produtos deste pedido');
                return $this->redirect(array('action' => 'produtos', $this->Pedido->id));
            }
            $this->Flash->error('Não foi possível cadastrar seu pedido, tente novamente.');
        }
    }

    public function produtos($pedido_id = null)
    {
        if (empty($pedido_id)) {
            $this->Flash->success('Pedido não informado, por favor, cadastre-o.');
            return $this->redirect(array('action' => 'novo'));
        }

        $pedido = $this->Pedido->findById($pedido_id);
        $this->loadModel('Produto');
        $produtos = $this->Produto->find('all');

        $this->loadModel('Comercial.Item');
        $itens = $this->Item->findByPedidoId($pedido_id);
        $this->set(compact('pedido', 'produtos', 'itens'));
    }

    public function produto($produto_id = null, $pedido_id = null)
    {
        if (empty($produto_id)) {
            $this->Flash->error('Produto não informado.');
            $this->redirect(array('plugin' => 'comercial', 'admin' => false, 'controller' => 'comercial', 'action' => 'index'));
        }

        if (empty($pedido_id)) {
            $this->Flash->error('Pedido não informado.');
            $this->redirect(array('plugin' => 'comercial', 'admin' => false, 'controller' => 'comercial', 'action' => 'index'));
        }

        $this->loadModel('Comercial.Item');
        
        $item = $this->Item->findByPedidoIdAndProdutoId($pedido_id, $produto_id);

        if (!empty($item)) {
            $this->Item->delete($item['Item']['id']);
            $this->Flash->success('Item retirado com sucesso.');
            return $this->redirect($this->getReferer());
        }

        $item = array('Item' => array(
            'pedido_id' => $pedido_id,
            'produto_id' => $produto_id,
            'qtde' => '1',
            'valor_unitario' => '0',
            'valor_total' => '0'
        ));

        if ($this->Item->save($item)) {
            $this->Flash->success('Continue adicionando produtos ao Pedido.');
        }
        
        return $this->redirect($this->getReferer());
    }

    public function conferir($pedido_id = null)
    {
        if (empty($pedido_id)) {
            $this->Flash->error('Pedido não informado.');
            return $this->redirect(array('plugin' => 'comercial', 'admin' => false, 'controller' => 'comercial', 'action' => 'index'));
        }

        $pedido = $this->Pedido->findById($pedido_id);

        $this->loadModel('Comercial.Item');
        $this->Item->recursive = 2;
        $itens = $this->Item->findByPedidoId($pedido_id);
        $this->set(compact('pedido', 'itens'));
    }

    public function atualizar()
    {
        $this->autoRender = false;

        extract($this->request->data);

        if (!empty($item_id)) {
            $this->loadModel('Comercial.Item');
            $this->Item->id = $item_id;
            $this->Item->saveField('qtde', $qtde);
            $this->Item->saveField('valor_unitario', $this->Utils->moeda_para_db($valor_unitario));
            $this->Item->saveField('valor_total', $this->Utils->moeda_para_db($valor_total))    ;
            return true;
        }

        return false;
    }
}