<?php

namespace Cadem\ReporteBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Cliente
 *
 * @ORM\Table(name="CLIENTE")
 * @ORM\Entity
 */
class Cliente
{
    /**
     * @var integer
     *
     * @ORM\Column(name="ID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="NOMBREFANTASIA", type="string", length=64, nullable=false)
     */
    private $nombrefantasia;

    /**
     * @var string
     *
     * @ORM\Column(name="RAZONSOCIAL", type="string", length=64, nullable=false)
     */
    private $razonsocial;

    /**
     * @var string
     *
     * @ORM\Column(name="RUT", type="string", length=16, nullable=true)
     */
    private $rut;

    /**
     * @var string
     *
     * @ORM\Column(name="LOGOFILENAME", type="string", length=128, nullable=true)
     */
    private $logofilename;

    /**
     * @var string
     *
     * @ORM\Column(name="LOGOSTYLE", type="string", length=128, nullable=true)
     */
    private $logostyle;

	/**
     * @var integer
     *
     * @ORM\Column(name="CANTIDADNIVELES", type="integer", nullable=false)
     */
    private $cantidadniveles;	
	
    /**
     * @var boolean
     *
     * @ORM\Column(name="ACTIVO", type="boolean", nullable=false)
     */
    private $activo;

	/**
     * @ORM\OneToMany(targetEntity="Usuario", mappedBy="cliente")
     */
	 
	protected $usuarios;
	 
	/**
    * @ORM\OneToMany(targetEntity="Estudio", mappedBy="cliente")
    */
	 
	protected $estudios;
	 
	/**
    * @ORM\OneToMany(targetEntity="Salacliente", mappedBy="cliente")
    */
	 
	protected $salaclientes;
	
	/**
    * @ORM\OneToMany(targetEntity="Noticia", mappedBy="cliente")
    */
	 
	protected $noticias;
	 
	 
	 
	 
	public function __construct()
    {
        $this->usuarios = new ArrayCollection();
        $this->estudios = new ArrayCollection();
        $this->salaclientes = new ArrayCollection();
        $this->noticias = new ArrayCollection();
    }


    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set nombrefantasia
     *
     * @param string $nombrefantasia
     * @return Cliente
     */
    public function setNombrefantasia($nombrefantasia)
    {
        $this->nombrefantasia = $nombrefantasia;
    
        return $this;
    }

    /**
     * Get nombrefantasia
     *
     * @return string 
     */
    public function getNombrefantasia()
    {
        return $this->nombrefantasia;
    }

    /**
     * Set razonsocial
     *
     * @param string $razonsocial
     * @return Cliente
     */
    public function setRazonsocial($razonsocial)
    {
        $this->razonsocial = $razonsocial;
    
        return $this;
    }

    /**
     * Get razonsocial
     *
     * @return string 
     */
    public function getRazonsocial()
    {
        return $this->razonsocial;
    }

    /**
     * Set rut
     *
     * @param string $rut
     * @return Cliente
     */
    public function setRut($rut)
    {
        $this->rut = $rut;
    
        return $this;
    }

    /**
     * Get rut
     *
     * @return string 
     */
    public function getRut()
    {
        return $this->rut;
    }

    /**
     * Set logofilename
     *
     * @param string $logofilename
     * @return Cliente
     */
    public function setLogofilename($logofilename)
    {
        $this->logofilename = $logofilename;
    
        return $this;
    }

    /**
     * Get logofilename
     *
     * @return string 
     */
    public function getLogofilename()
    {
        return $this->logofilename;
    }

    /**
     * Set logostyle
     *
     * @param string $logostyle
     * @return Cliente
     */
    public function setLogostyle($logostyle)
    {
        $this->logostyle = $logostyle;
    
        return $this;
    }

    /**
     * Get logostyle
     *
     * @return string 
     */
    public function getLogostyle()
    {
        return $this->logostyle;
    }

    /**
     * Set cantidadniveles
     *
     * @param integer $cantidadniveles
     * @return Cliente
     */
    public function setCantidadniveles($cantidadniveles)
    {
        $this->cantidadniveles = $cantidadniveles;
    
        return $this;
    }

    /**
     * Get cantidadniveles
     *
     * @return integer
     */
    public function getCantidadniveles()
    {
        return $this->cantidadniveles;
    }	
	
    /**
     * Set activo
     *
     * @param boolean $activo
     * @return Cliente
     */
    public function setActivo($activo)
    {
        $this->activo = $activo;
    
        return $this;
    }

    /**
     * Get activo
     *
     * @return boolean 
     */
    public function getActivo()
    {
        return $this->activo;
    }

    /**
     * Set id
     *
     * @param integer $id
     * @return Cliente
     */
    public function setId($id)
    {
        $this->id = $id;
    
        return $this;
    }

    /**
     * Add usuarios
     *
     * @param \Cadem\ReporteBundle\Entity\Usuario $usuarios
     * @return Cliente
     */
    public function addUsuario(\Cadem\ReporteBundle\Entity\Usuario $usuarios)
    {
        $this->usuarios[] = $usuarios;
    
        return $this;
    }

    /**
     * Remove usuarios
     *
     * @param \Cadem\ReporteBundle\Entity\Usuario $usuarios
     */
    public function removeUsuario(\Cadem\ReporteBundle\Entity\Usuario $usuarios)
    {
        $this->usuarios->removeElement($usuarios);
    }

    /**
     * Get usuarios
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getUsuarios()
    {
        return $this->usuarios;
    }

    /**
     * Add estudios
     *
     * @param \Cadem\ReporteBundle\Entity\Estudio $estudios
     * @return Cliente
     */
    public function addEstudio(\Cadem\ReporteBundle\Entity\Estudio $estudios)
    {
        $this->estudios[] = $estudios;
    
        return $this;
    }

    /**
     * Remove estudios
     *
     * @param \Cadem\ReporteBundle\Entity\Estudio $estudios
     */
    public function removeEstudio(\Cadem\ReporteBundle\Entity\Estudio $estudios)
    {
        $this->estudios->removeElement($estudios);
    }

    /**
     * Get estudios
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getEstudios()
    {
        return $this->estudios;
    }
	
	/**
     * Add salacliente
     *
     * @param \Cadem\ReporteBundle\Entity\Salacliente $salaclientes
     * @return Cliente
     */
    public function addSalacliente(\Cadem\ReporteBundle\Entity\Salacliente $salaclientes)
    {
        $this->salaclientes[] = $salaclientes;
    
        return $this;
    }

    /**
     * Remove salacliente
     *
     * @param \Cadem\ReporteBundle\Entity\Salacliente $salaclientes
     */
    public function removeSalacliente(\Cadem\ReporteBundle\Entity\Salacliente $salaclientes)
    {
        $this->salaclientes->removeElement($salaclientes);
    }

    /**
     * Get salaclientes
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getSalacliente()
    {
        return $this->salaclientes;
    }
	
	/**
     * Add noticia
     *
     * @param \Cadem\ReporteBundle\Entity\Noticia $noticia
     * @return Cliente
     */
    public function addNoticia(\Cadem\ReporteBundle\Entity\Noticia $noticia)
    {
        $this->noticias[] = $noticia;
    
        return $this;
    }
	
	/**
     * Remove noticia
     *
     * @param \Cadem\ReporteBundle\Entity\Noticia $noticia
     */
    public function removeNoticia(\Cadem\ReporteBundle\Entity\Noticia $noticia)
    {
        $this->salaclientes->removeElement($noticia);
    }

    /**
     * Get noticias
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getNoticias()
    {
        return $this->noticias;
    }
}