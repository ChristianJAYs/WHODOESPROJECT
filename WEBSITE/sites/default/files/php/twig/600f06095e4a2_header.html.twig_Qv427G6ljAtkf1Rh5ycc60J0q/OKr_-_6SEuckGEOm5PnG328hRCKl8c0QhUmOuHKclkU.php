<?php

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Extension\SandboxExtension;
use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Source;
use Twig\Template;

/* @zuvi/template-parts/header.html.twig */
class __TwigTemplate_692f45f45bfd4cb04b0973ae6535280c75f0bd6d149ad130f1ad4684d2ff70bc extends \Twig\Template
{
    private $source;
    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
        ];
        $this->sandbox = $this->env->getExtension('\Twig\Extension\SandboxExtension');
        $this->checkSecurity();
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 1
        echo "<!-- Start: Header -->
<header class=\"header clear\">
  <div class=\"header-circle header-circle1\"></div>
  <div class=\"header-circle header-circle2\"></div>
  <div class=\"header-circle header-circle3\"></div>
  <div class=\"header-circle header-circle4\"></div>
  <div class=\"header-circle header-circle5\"></div>
  <div class=\"header-circle header-circle6\"></div>
  <div class=\"container header-main\">
    ";
        // line 10
        if (twig_get_attribute($this->env, $this->source, ($context["page"] ?? null), "site_branding", [], "any", false, false, true, 10)) {
            // line 11
            echo "      <div class=\"site-brand\">
        ";
            // line 12
            echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(twig_get_attribute($this->env, $this->source, ($context["page"] ?? null), "site_branding", [], "any", false, false, true, 12), 12, $this->source), "html", null, true);
            echo "
      </div> <!--/.site-branding -->
    ";
        }
        // line 15
        echo "    ";
        if ((twig_get_attribute($this->env, $this->source, ($context["page"] ?? null), "primary_menu", [], "any", false, false, true, 15) || twig_get_attribute($this->env, $this->source, ($context["page"] ?? null), "search_box", [], "any", false, false, true, 15))) {
            // line 16
            echo "      <div class=\"header-main-right\">
        ";
            // line 17
            if (twig_get_attribute($this->env, $this->source, ($context["page"] ?? null), "primary_menu", [], "any", false, false, true, 17)) {
                // line 18
                echo "          <div class=\"mobile-menu\"><i class=\"icon-menu\"></i></div> ";
                // line 19
                echo "          <div class=\"primary-menu-wrapper\">
            <div class=\"menu-wrap\">
              <div class=\"close-mobile-menu\">x</div>
              ";
                // line 22
                echo $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, $this->sandbox->ensureToStringAllowed(twig_get_attribute($this->env, $this->source, ($context["page"] ?? null), "primary_menu", [], "any", false, false, true, 22), 22, $this->source), "html", null, true);
                echo "
            </div> <!-- /.menu-wrap -->
          </div> <!-- /.primary-menu-wrapper -->
        ";
            }
            // line 26
            echo "        ";
            if (twig_get_attribute($this->env, $this->source, ($context["page"] ?? null), "search_box", [], "any", false, false, true, 26)) {
                // line 27
                echo "          ";
                $this->loadTemplate("@zuvi/template-parts/search.html.twig", "@zuvi/template-parts/header.html.twig", 27)->display($context);
                // line 28
                echo "        ";
            }
            // line 29
            echo "      </div> <!-- /.header-right -->
    ";
        }
        // line 31
        echo "  </div> <!-- /.container -->
  ";
        // line 32
        if ((($context["is_front"] ?? null) && ($context["slider_show"] ?? null))) {
            // line 33
            echo "    ";
            $this->loadTemplate("@zuvi/template-parts/slider.html.twig", "@zuvi/template-parts/header.html.twig", 33)->display($context);
            // line 34
            echo "  ";
        } elseif ( !($context["is_front"] ?? null)) {
            // line 35
            echo "    ";
            $this->loadTemplate("@zuvi/template-parts/page_header.html.twig", "@zuvi/template-parts/header.html.twig", 35)->display($context);
            // line 36
            echo "  ";
        }
        // line 37
        echo "</header>
";
        // line 38
        if ((($context["is_front"] ?? null) && ($context["slider_show"] ?? null))) {
            // line 39
            echo "<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 1440 160\"><path fill=\"#020312\" fill-opacity=\"1\" d=\"M0,32L60,26.7C120,21,240,11,360,42.7C480,75,600,149,720,154.7C840,160,960,96,1080,74.7C1200,53,1320,75,1380,85.3L1440,96L1440,0L1380,0C1320,0,1200,0,1080,0C960,0,840,0,720,0C600,0,480,0,360,0C240,0,120,0,60,0L0,0Z\"></path></svg>
";
        }
        // line 41
        echo "<!-- End: Header -->
";
    }

    public function getTemplateName()
    {
        return "@zuvi/template-parts/header.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  122 => 41,  118 => 39,  116 => 38,  113 => 37,  110 => 36,  107 => 35,  104 => 34,  101 => 33,  99 => 32,  96 => 31,  92 => 29,  89 => 28,  86 => 27,  83 => 26,  76 => 22,  71 => 19,  69 => 18,  67 => 17,  64 => 16,  61 => 15,  55 => 12,  52 => 11,  50 => 10,  39 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("", "@zuvi/template-parts/header.html.twig", "D:\\xampp\\htdocs\\Hardinera\\themes\\zuvi\\templates\\template-parts\\header.html.twig");
    }
    
    public function checkSecurity()
    {
        static $tags = array("if" => 10, "include" => 27);
        static $filters = array("escape" => 12);
        static $functions = array();

        try {
            $this->sandbox->checkSecurity(
                ['if', 'include'],
                ['escape'],
                []
            );
        } catch (SecurityError $e) {
            $e->setSourceContext($this->source);

            if ($e instanceof SecurityNotAllowedTagError && isset($tags[$e->getTagName()])) {
                $e->setTemplateLine($tags[$e->getTagName()]);
            } elseif ($e instanceof SecurityNotAllowedFilterError && isset($filters[$e->getFilterName()])) {
                $e->setTemplateLine($filters[$e->getFilterName()]);
            } elseif ($e instanceof SecurityNotAllowedFunctionError && isset($functions[$e->getFunctionName()])) {
                $e->setTemplateLine($functions[$e->getFunctionName()]);
            }

            throw $e;
        }

    }
}
