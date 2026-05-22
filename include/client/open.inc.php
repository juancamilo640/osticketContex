<?php
if(!defined('OSTCLIENTINC')) die('Access Denied!');
$info=array();
if($thisclient && $thisclient->isValid()) {
    $info=array('name'=>$thisclient->getName(),
                'email'=>$thisclient->getEmail(),
                'phone'=>$thisclient->getPhoneNumber());
}

$info=($_POST && $errors)?Format::htmlchars($_POST):$info;

$form = null;
if (!$info['topicId']) {
    if (array_key_exists('topicId',$_GET) && preg_match('/^\d+$/',$_GET['topicId']) && Topic::lookup($_GET['topicId']))
        $info['topicId'] = intval($_GET['topicId']);
    else
        $info['topicId'] = $cfg->getDefaultTopicId();
}

$forms = array();
if ($info['topicId'] && ($topic=Topic::lookup($info['topicId']))) {
    foreach ($topic->getForms() as $F) {
        if (!$F->hasAnyVisibleFields())
            continue;
        if ($_POST) {
            $F = $F->instanciate();
            $F->isValidForClient();
        }
        $forms[] = $F->getForm();
    }
}

// Get topics that are public
$allTopicsData = Topic::getHelpTopics(true, false, true, array(), true);
$deptTopicsMap = array();
$deptNames = array();
$allTopics = array();

// Get the system default department ID
$defaultDeptId = $cfg->getDefaultDeptId();
// Get all public/active departments
$publicDepts = Dept::getDepartments(array('publiconly' => true, 'activeonly' => true));

// Pre-populate deptNames with all public departments to ensure they appear
foreach ($publicDepts as $did => $dname) {
    $deptNames[$did] = $dname;
    $deptTopicsMap[$did] = array();
}

foreach ($allTopicsData as $tid => $tinfo) {
    $deptId = intval($tinfo['dept_id']);
    $categoryId = ($deptId === 0) ? $defaultDeptId : $deptId;

    $dept = Dept::lookup($categoryId);
    if (!$dept || !$dept->isActive()) {
        continue; // Skip topics belonging to inactive or unavailable departments
    }

    if (!isset($deptNames[$categoryId])) {
        $deptNames[$categoryId] = $dept->getLocalName();
        $deptTopicsMap[$categoryId] = array();
    }

    $deptTopicsMap[$categoryId][$tid] = $tinfo['topic'];
    $allTopics[$tid] = array(
        'name' => $tinfo['topic'],
        'deptId' => $categoryId,
    );
}

// Remove departments that ended up with no topics (optional, but cleaner)
foreach ($deptTopicsMap as $did => $topics) {
    if (empty($topics)) {
        unset($deptNames[$did]);
        unset($deptTopicsMap[$did]);
    }
}

// Detect the department from the selected topic or from a previous department selection on POST
$selectedDeptId = '';
if ($_POST && isset($_POST['deptFilter']) && preg_match('/^\d+$/', $_POST['deptFilter'])) {
    $selectedDeptId = intval($_POST['deptFilter']);
}
if ($info['topicId'] && isset($allTopics[$info['topicId']])) {
    $selectedDeptId = intval($allTopics[$info['topicId']]['deptId']);
}

$topicOptions = array();
if ($selectedDeptId && isset($deptTopicsMap[$selectedDeptId])) {
    foreach ($deptTopicsMap[$selectedDeptId] as $id => $name) {
        $topicOptions[$id] = $name;
    }
}
?>
<style>
.support-page {
    width: 100%;
    max-width: 980px;
    margin: 0 auto;
    padding: 1rem;
}
.support-form-card {
    background: #ffffff;
    border: 1px solid #e4e7eb;
    border-radius: 1rem;
    box-shadow: 0 20px 60px rgba(15, 23, 42, 0.08);
    padding: 2rem;
}
.support-form-card h1 {
    margin: 0 0 0.5rem;
    font-size: 2rem;
    color: #1f2937;
}
.support-form-card p.lead {
    margin: 0 0 2rem;
    color: #4b5563;
    line-height: 1.65;
}
.support-form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}
.form-group {
    margin-bottom: 1.5rem;
}
.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: #111827;
}
.form-control,
.form-control:focus {
    width: 100%;
    border: 1px solid #d1d5db;
    border-radius: 0.75rem;
    padding: 0.95rem 1rem;
    background: #f9fafb;
    color: #111827;
    box-shadow: none;
    font-size: 1rem;
}
.form-control:focus {
    border-color: #2563eb;
    background: #ffffff;
    outline: none;
}
.form-header {
    margin-bottom: 1rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid #e5e7eb;
    font-size: 1.1rem;
    font-weight: 700;
    color: #0f172a;
}
.form-note {
    margin-top: 0.5rem;
    color: #475569;
    font-size: 0.95rem;
}
.form-error {
    margin-top: 0.5rem;
    color: #dc2626;
    font-size: 0.95rem;
}
.form-actions {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 0.75rem;
    margin-top: 1.5rem;
}
.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    min-width: 10rem;
    padding: 0.95rem 1.4rem;
    border-radius: 0.75rem;
    border: none;
    font-size: 1rem;
    cursor: pointer;
}
.btn-primary {
    background: #2563eb;
    color: #ffffff;
}
.btn-secondary {
    background: #f3f4f6;
    color: #111827;
}
.btn-secondary:hover,
.btn-primary:hover {
    opacity: 0.95;
}
@media (max-width: 640px) {
    .support-form-card { padding: 1.25rem; }
    .support-form-grid { grid-template-columns: 1fr; }
}
</style>
<div class="support-page">
    <div class="support-form-card">
        <header class="form-header">
            <h1><?php echo __('Open a New Ticket'); ?></h1>
            <p class="lead"><?php echo __('Por favor complete el siguiente formulario para abrir un nuevo ticket.'); ?></p>
        </header>
        <form id="ticketForm" method="post" action="open.php" enctype="multipart/form-data">
            <?php csrf_token(); ?>
            <input type="hidden" name="a" value="open">

            <?php
                if (!$thisclient) {
                    $uform = UserForm::getUserForm()->getForm($_POST);
                    if ($_POST) {
                        $uform->isValid();
                    }
                    $uform->render(array('staff' => false, 'mode' => 'create'));
                } else { ?>
                    <div class="support-form-grid">
                        <div class="form-group">
                            <label><?php echo __('Client'); ?></label>
                            <div><?php echo Format::htmlchars($thisclient->getName()); ?></div>
                        </div>
                        <div class="form-group">
                            <label><?php echo __('Email'); ?></label>
                            <div><?php echo $thisclient->getEmail(); ?></div>
                        </div>
                    </div>
                <?php } ?>

            <div class="form-group">
                <label for="deptFilter"><?php echo __('Departmento'); ?></label>
                <select id="deptFilter" name="deptFilter" class="form-control">
                    <option value="">&mdash; <?php echo __('Seleccione un Departamento'); ?> &mdash;</option>
                    <?php foreach ($deptNames as $did => $dname): ?>
                        <option value="<?php echo $did; ?>" <?php echo (strval($selectedDeptId) === strval($did)) ? 'selected="selected"' : ''; ?>>
                            <?php echo Format::htmlchars($dname); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="form-note"></div> <br/><br>
            </div>

            <div id="topic-section" class="form-group" style="<?php echo $selectedDeptId !== '' ? '' : ''; ?>">
                <label for="topicId"><?php echo __('Tema de ayuda'); ?></label>
                <select id="topicId" name="topicId" class="form-control" <?php echo $selectedDeptId === '' ? 'disabled' : ''; ?> >
                    <option value="" selected="selected">&mdash; <?php echo __('Seleccione un tema de ayuda'); ?> &mdash;</option>
                    <?php foreach ($allTopics as $id => $topic): ?>
                        <?php if ($selectedDeptId === '' || $topic['deptId'] == $selectedDeptId): ?>
                            <option value="<?php echo $id; ?>"
                                data-dept="<?php echo $topic['deptId']; ?>"
                                <?php echo ($info['topicId'] == $id) ? 'selected="selected"' : ''; ?>>
                                <?php echo Format::htmlchars($topic['name']); ?>
                            </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
                <div id="topicHint" class="form-note"></div>
                <div id="topicNoOptions" class="form-note" style="display:<?php echo $selectedDeptId !== '' && empty($topicOptions) ? 'block' : 'none'; ?>; color:#b91c1c;"><?php echo __('No hay temas de ayuda disponibles para el departamento seleccionado.'); ?></div>
                <div id="topicError" class="form-error"><?php echo $errors['topicId']; ?></div>
            </div>

            <div id="dynamic-form-wrapper">
                <div id="dynamic-form-placeholder" class="form-note"><br><br></div>
                <div id="dynamic-form">
                    <?php
                    $options = array('mode' => 'create');
                    foreach ($forms as $form) {
                        include(CLIENTINC_DIR . 'templates/dynamic-form.tmpl.php');
                    } ?>
                </div>
            </div>

            <?php
            if ($cfg && $cfg->isCaptchaEnabled() && (!$thisclient || !$thisclient->isValid())) {
                if ($_POST && $errors && !$errors['captcha']) {
                    $errors['captcha'] = __('Please re-enter the text again');
                }
                ?>
                <div class="form-group captchaRow">
                    <label for="captcha"><?php echo __('CAPTCHA Text'); ?></label>
                    <div>
                        <span class="captcha"><img src="captcha.php" border="0" align="left"></span>
                        <input id="captcha" type="text" name="captcha" size="6" autocomplete="off" class="form-control">
                        <div class="form-note"><?php echo __('Enter the text shown on the image.'); ?></div>
                        <div class="form-error"><?php echo $errors['captcha']; ?></div>
                    </div>
                </div>
            <?php } ?>

            <div class="form-actions">
                <button id="submitTicket" type="submit" class="btn btn-primary" <?php echo $info['topicId'] ? '' : 'disabled="disabled"'; ?>><?php echo __('Create Ticket'); ?></button>
                <button type="reset" class="btn btn-secondary"><?php echo __('Reset'); ?></button>
                <button type="button" class="btn btn-secondary" onclick="$('.richtext').each(function() {
                    var redactor = $(this).data('redactor');
                    if (redactor && redactor.opts.draftDelete) {
                        redactor.plugin.draft.deleteDraft();
                    }
                }); window.location.href='index.php';">
                    <?php echo __('Cancel'); ?>
                </button>
            </div>
        </form>
    </div>
</div>

<script type="text/javascript">
(function($) {
    var deptTopicsMap = <?php echo json_encode($deptTopicsMap); ?>;
    var $deptFilter = $('#deptFilter');
    var $topicSection = $('#topic-section');
    var $topicSelect = $('#topicId');
    var $topicError = $('#topicError');

    function renderTopicOptions(deptId) {
        $topicSelect.find('option').not(':first').remove();
        $topicSelect.val('');

        if (!deptId || !deptTopicsMap[deptId]) {
            return $();
        }

        Object.entries(deptTopicsMap[deptId]).forEach(function(entry) {
            var topicId = entry[0];
            var topicName = entry[1];
            $('<option>', {
                'value': topicId,
                'text': topicName,
                'data-topic': topicId
            }).appendTo($topicSelect);
        });

        return $topicSelect.find('option[data-topic]');
    }

    function refreshTopicOptions(deptId) {
        var newOptions = renderTopicOptions(deptId);
        var hasDept = !!deptId;
        var hasTopics = newOptions.length > 0;

        if (!hasDept) {
            $topicSelect.prop('disabled', true);
            $topicSection.show();
            $('#dynamic-form').empty();
            $('#dynamic-form-placeholder').show();
            $('#topicNoOptions').hide();
            return;
        }

        $topicSelect.prop('disabled', !hasTopics);
        $topicSection.show();

        if (!hasTopics) {
            $('#dynamic-form').empty();
            $('#dynamic-form-placeholder').show();
            $('#topicNoOptions').show();
            return;
        }

        $('#topicNoOptions').hide();
        $('#dynamic-form-placeholder').show();
    }

    function updateSubmitState() {
        var canSubmit = !!$topicSelect.val();
        $('#submitTicket').prop('disabled', !canSubmit);
    }

    $deptFilter.on('change', function() {
        refreshTopicOptions($(this).val());
        updateSubmitState();
    });

    $topicSelect.on('change', function() {
        var topicId = $(this).val();
        $('#dynamic-form').empty();
        $topicError.text('');

        if (!topicId) {
            updateSubmitState();
            $('#dynamic-form-placeholder').show();
            return;
        }

        updateSubmitState();
        $('#dynamic-form-placeholder').hide();
        var data = $(':input[name]', '#ticketForm').serialize();
        $.ajax('ajax.php/form/help-topic/' + topicId, {
            data: data,
            dataType: 'json',
            success: function(json) {
                $('#dynamic-form').empty().append(json.html);
                $(document.head).append(json.media);
                if ($.trim(json.html) === '') {
                    $('#dynamic-form-placeholder').show();
                }
            },
            error: function() {
                $topicError.text('<?php echo __('Unable to load topic fields at this time. Please try again.'); ?>');
            }
        });
    });

    refreshTopicOptions($deptFilter.val());
    updateSubmitState();
    if ($('#dynamic-form').children().length) {
        $('#dynamic-form-placeholder').hide();
    }
})(jQuery);
</script>
