
var cora = {
    strings: {}
};

(function() {
    var idx, len, chain;

    $LAB.setGlobalDefaults({AlwaysPreserveOrder: true});
    chain = $LAB;
    for (idx = 0, len = _srcs.framework.length; idx < len; ++idx) {
        chain = chain.script(_srcs.framework[idx]);
    }
    for (idx = 0, len = _srcs.main.length; idx < len; ++idx) {
        chain = chain.script(_srcs.main[idx]);
    }
    chain.wait(function() {
        Locale.use("de-DE");
        gui.initialize();

        cora.settings.initialize();
        cora.tagsets.performUpdate();
        cora.fileImporter.initialize();
        cora.fileManager.initialize();
        cora.projects.onUpdate(cora.fileManager.render.bind(cora.fileManager));
        cora.projects.performUpdate();

        gui.hideTabButton('edit');
        gui.hideTabButton('search');
        gui.changeTab(default_tab);
        gui.showNews();
    });

    if (userdata.admin) {
        for (idx = 0, len = _srcs.admin.length; idx < len; ++idx) {
            chain = chain.script(_srcs.admin[idx]);
        }
        chain = chain.wait(function() {
            cora.noticeEditor.initialize();
            cora.projects.onInit(cora.userEditor.initialize.bind(cora.userEditor));
            cora.projectEditor.initialize();
            cora.tagsetEditor.initialize();
            cora.annotatorEditor.initialize();
            gui.addToggleEventCollapseAll('adminViewCollapseAll', 'div#adminDiv');
            gui.addToggleEventExpandAll('adminViewExpandAll', 'div#adminDiv');
        });
    }
}());

function onBeforeUnload() {
    if (cora.editor !== null && cora.editor.hasUnsavedChanges()) {
        cora.editor.save();
	return ("Es gibt noch ungespeicherte Änderungen, die verloren gehen könnten, wenn Sie fortfahren!");
    }
}