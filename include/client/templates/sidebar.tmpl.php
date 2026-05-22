<?php
$BUTTONS = isset($BUTTONS) ? $BUTTONS : true;
?>
    <div class="sidebar pull-right">
<?php if ($BUTTONS) { ?>
        <div class="front-page-button flush-right">
<?php
    // En la página principal solo se muestra el botón de iniciar sesión
    // si el usuario NO está autenticado. Si ya inició sesión, mostramos
    // los botones habituales de nuevo ticket y estado.
    if ($thisclient && is_object($thisclient) && $thisclient->isValid() && !$thisclient->isGuest()) {
        // Usuario autenticado: botones completos
?>
<p>
<?php
        if ($cfg->getClientRegistrationMode() != 'disabled'
            || !$cfg->isClientLoginRequired()) { ?>
            <a href="open.php" style="display:block" class="blue button"><?php
                echo __('Open a New Ticket');?></a>
</p>
<p>
<?php } ?>
            <a href="view.php" style="display:block" class="green button"><?php
                echo __('Check Ticket Status');?></a>
</p>
<?php
    } else {
        // Usuario NO autenticado: solo botón de inicio de sesión
?>
<p>
        <a href="login.php" style="display:block" class="blue button"><?php
            echo __('Sign In');?></a>
</p>
<?php
    }
?>
        </div>
<?php } ?>
        <div class="content"><?php
    if ($cfg->isKnowledgebaseEnabled()
        && ($faqs = FAQ::getFeatured()->select_related('category')->limit(5))
        && $faqs->all()) { ?>
            <section><div class="header"><?php echo __('Featured Questions'); ?></div>
<?php   foreach ($faqs as $F) { ?>
            <div><a href="<?php echo ROOT_PATH; ?>kb/faq.php?id=<?php
                echo urlencode($F->getId());
                ?>"><?php echo $F->getLocalQuestion(); ?></a></div>
<?php   } ?>
            </section>
<?php
    }
    $resources = Page::getActivePages()->filter(array('type'=>'other'));
    if ($resources->all()) { ?>
            <section><div class="header"><?php echo __('Other Resources'); ?></div>
<?php   foreach ($resources as $page) { ?>
            <div><a href="<?php echo ROOT_PATH; ?>pages/<?php echo $page->getNameAsSlug();
            ?>"><?php echo $page->getLocalName(); ?></a></div>
<?php   } ?>
            </section>
<?php
    }
        ?></div>
    </div>

