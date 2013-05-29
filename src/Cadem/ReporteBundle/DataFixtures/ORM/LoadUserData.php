<?php

namespace Cadem\ReporteBundle\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Cadem\ReporteBundle\Entity\Cliente;
use Cadem\ReporteBundle\Entity\Logo;
use Cadem\ReporteBundle\Entity\Variable;
use Cadem\ReporteBundle\Entity\VariableCliente;
use Cadem\ReporteBundle\Entity\Estudio;

class LoadUserData implements FixtureInterface, ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;


    /**
     * {@inheritDoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }


    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
	// $cliente_soprole = new Cliente();
	// $cliente_soprole->setNombre('soprole');
	// $cliente_soprole->setRut('111-1');
	// $cliente_soprole->setTipo('fabricante');
	// $cliente_nestle = new Cliente();
	// $cliente_nestle->setNombre('nestle');
	// $cliente_nestle->setRut('111-2');
	// $cliente_nestle->setTipo('fabricante');
	// $cliente_johnson = new Cliente();
	// $cliente_johnson->setNombrefantasia('johnson');
	// $cliente_johnson->setRazonsocial('johnson');
	// $cliente_johnson->setRut('111-3');
	// $cliente_johnson->setLogofilename('logojohnson.png');
	// $cliente_johnson->setLogostyle('');
	// $cliente_johnson->setActivo(true);
	// $cliente_johnson->setId(1);
        
	
	$userManager = $this->container->get('fos_user.user_manager');
 	
	// $user1 = $userManager->createUser();
	// $user1->setUsername('soprole');
	// $user1->setEmail('usuario@soprole.cl');
	// $user1->setPlainPassword('1234');
	// $user1->setCliente($cliente_soprole);
	// $user1->setEnabled(true);
	// $user1->setRut('111-1');
	// $userManager->updateUser($user1,false);
	
 	// $user2 = $userManager->createUser();
	// $user2->setUsername('nestle');
	// $user2->setEmail('usuario@nestle.cl');
	// $user2->setPlainPassword('1234');
	// $user2->setCliente($cliente_nestle);
	// $user2->setEnabled(true);
	// $userManager->updateUser($user2,false);
	
	$user3 = $userManager->createUser();
	$user3->setUsername('johnson');
	$user3->setEmail('usuario@johnson.cl');
	$user3->setPlainPassword('1234');
	$user3->setIdCliente(12);
	$user3->setEnabled(true);
	$user3->setRut('111-1');
	$userManager->updateUser($user3,false);
	
	$est1 = new Estudio();
	$est1->setId(1)
		 ->setNombre('quiebre')
		 ->setActivo(true)
		 ->setCliente($cliente_johnson);
	$manager->persist($est1);

	// $logo1 = new Logo();
	// $logo1->setFilename('logosoprole.gif');	
	// $logo1->setWidth('auto');	
	// $logo1->setHeight('auto');	
	// $logo1->setActivo(true);
	// $logo1->setCliente($cliente_soprole);
	// $logo2 = new Logo();
	// $logo2->setFilename('logonestle.jpg');	
	// $logo2->setWidth('auto');
	// $logo2->setHeight('auto');	
	// $logo2->setActivo(true);	
	// $logo2->setCliente($cliente_nestle);
	// $logo3 = new Logo();
	// $logo3->setFilename('logojohnson.png');	
	// $logo3->setWidth('auto');
	// $logo3->setHeight('auto');	
	// $logo3->setActivo(true);	
	// $logo3->setCliente($cliente_johnson);
	
	// $var1 = new Variable();
	// $var1->setNombre('quiebre');	
	// $var1->setDescripcion('ausencia de sku según planograma');	
	// $var1->setActivo(true);
	// $var2 = new Variable();
	// $var2->setNombre('presencia');	
	// $var2->setDescripcion('presencia de sku según planograma');	
	// $var2->setActivo(true);		
	// $var3 = new Variable();	
	// $var3->setNombre('precio');	
	// $var3->setDescripcion('precio de sku para período actual');	
	// $var3->setActivo(true);	
	// $var4 = new Variable();	
	// $var4->setNombre('cobertura');	
	// $var4->setDescripcion('cobertura actual');	
	// $var4->setActivo(true);	

	// $manager->persist($cliente_soprole);
	// $manager->persist($cliente_nestle);
	//$manager->persist($cliente_johnson);
	// $manager->persist($logo1);
	// $manager->persist($logo2);	
	// $manager->persist($logo3);	
	// $manager->persist($var1);
	// $manager->persist($var2);	
	// $manager->persist($var3);	
	// $manager->persist($var4);	
	

	$varCli1 = new VariableCliente();
	$varCli1->setCliente($cliente_soprole)
	->setVariable($var1)
	->setActivo(true);

	// $varCli2 = new VariableCliente();
	// $varCli2->setCliente($cliente_soprole);	
	// $varCli2->setVariable($var3);		
	// $varCli2->setActivo(true);		
	// $varCli3 = new VariableCliente();
	// $varCli3->setCliente($cliente_nestle);	
	// $varCli3->setVariable($var1);		
	// $varCli3->setActivo(true);			
	// $varCli4 = new VariableCliente();
	// $varCli4->setCliente($cliente_nestle);	
	// $varCli4->setVariable($var2);		
	// $varCli4->setActivo(true);			
	// $varCli5 = new VariableCliente();
	// $varCli5->setCliente($cliente_nestle);	
	// $varCli5->setVariable($var3);		
	// $varCli5->setActivo(true);
	// $varCli6 = new VariableCliente();
	// $varCli6->setCliente($cliente_soprole);	
	// $varCli6->setVariable($var4);		
	// $varCli6->setActivo(true);
	

	$varCli7 = new VariableCliente();
	$varCli7->setCliente($cliente_johnson)
	->setVariable($var3)
	->setActivo(true);
	$varCli8 = new VariableCliente();
	$varCli8->setCliente($cliente_johnson)
	->setVariable($var4)
	->setActivo(true);

	
	// $manager->persist($varCli1);
	// $manager->persist($varCli2);	
	// $manager->persist($varCli3);	
	// $manager->persist($varCli4);	
	// $manager->persist($varCli5);			
	// $manager->persist($varCli6);			
	// $manager->persist($varCli7);			
	// $manager->persist($varCli8);

	$manager->flush();
    }
}
	