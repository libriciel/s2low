$(document).ready(function () {
    const authoritySelect = $("select[name='authority_id']");

    const showPermissionsOfSelectedAuthority = function () {
        const selectedOption = authoritySelect.find("option:selected");
        const offeredModules = String(selectedOption.data("permission-modules") ?? "");

        $("[data-permission-module]").each(function () {
            const permission = $(this);
            const isOffered = !selectedOption.val()
                || offeredModules.split(",").includes(String(permission.data("permission-module")));
            permission.toggle(isOffered);
        });
    };

    authoritySelect.on("change", showPermissionsOfSelectedAuthority);
    showPermissionsOfSelectedAuthority();
});
