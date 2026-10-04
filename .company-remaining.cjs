const fs = require('fs');
function edit(path, change) {
    const original = fs.readFileSync(path, 'utf8');
    const updated = change(original.replace(/\r\n/g, '\n'));
    fs.writeFileSync(path, updated);
}
function replace(text, before, after) {
    if (!text.includes(before)) throw new Error('Missing anchor: ' + before);
    return text.replace(before, after);
}
edit('routes/web.php', text => text.replace(/->name\('(attendance|travel-allowance)\.([^']+)'\)/g, (match, group, action) => {
    if (action === 'index') return match;
    return `->middleware('active-company:${group === 'attendance' ? 'attendance' : 'travel'}')\n        ${match}`;
}));
edit('routes/settings.php', text => text.replace(/->name\('(profile\.update|settings\.users\.(?:store|update)|settings\.employee\.(?:store|toggle-status|resend-invitation))'\)/g, (match, name) => {
    const mode = name === 'profile.update' ? 'input' : name.startsWith('settings.users.') ? 'assignments' : 'employee';
    return `->middleware('active-company:${mode}')\n                ${match}`;
}));
for (const path of [
    'app/Http/Controllers/AttendanceController.php',
    'app/Http/Controllers/TravelAllowance/TravelAllowanceController.php',
    'app/Http/Controllers/Settings/ProfileController.php',
]) {
    edit(path, text => replace(text, "->clients()\n", "->clients()\n            ->where('clients.is_active', true)\n"));
}
for (const path of ['app/Http/Controllers/AttendanceController.php', 'app/Http/Requests/StoreTravelAllowanceRequest.php', 'app/Http/Requests/Settings/ProfileUpdateRequest.php']) {
    edit(path, text => text.replace(/(Rule::exists\('client_user', 'client_id'\)->where\([\s\S]*?\n\s*\),)/g,
        "$1\n                Rule::exists('clients', 'id')->where('is_active', true),"));
}
edit('app/Http/Controllers/Admin/Settings/UserSettingsController.php', text => {
    text = replace(text, "'clients:id,company_name'", "'clients:id,company_name,is_active'");
    text = replace(text, "'companies' => Client::query()", "'companies' => Client::query()->active()");
    text = replace(text, "->select('id', 'company_name')", "->select('id', 'company_name', 'is_active')");
    text = replace(text, "'client_ids.*' => ['required', 'exists:clients,id']", "'client_ids.*' => ['required', 'integer', Rule::exists('clients', 'id')->where('is_active', true)]");
    text = replace(text, "'client_ids.*' => ['required', 'exists:clients,id']", `// Existing inactive assignments may be retained, but cannot be newly assigned.
            'client_ids.*' => ['required', 'integer', Rule::exists('clients', 'id')->where(
                fn ($query) => $query->where('is_active', true)
                    ->orWhereIn('id', $user->clients()->pluck('clients.id'))
            )]`);
    text = replace(text, "'work_detail' => ['required', 'array']", "'work_detail' => ['nullable', 'array']");
    text = replace(text, "'work_detail.client_id' => ['required', 'integer', 'exists:clients,id']", "'work_detail.client_id' => ['required_with:work_detail', 'integer', Rule::exists('clients', 'id')->where('is_active', true)]");
    text = text.replace(/('work_detail\.[^']+' => \[)'required'/g, "$1'required_with:work_detail'");
    text = replace(text, "if (! in_array((int) $validated['work_detail']['client_id'], $clientIds, true))", "if (! empty($validated['work_detail']) && ! in_array((int) $validated['work_detail']['client_id'], array_map('intval', $clientIds), true))");
    const updateStart = text.indexOf('public function update(');
    const head = text.slice(0, updateStart);
    let tail = text.slice(updateStart);
    tail = replace(tail, "unset($validated['client_ids']);", "$workDetail = $validated['work_detail'] ?? null;\n        unset($validated['client_ids'], $validated['work_detail']);");
    // Retain inactive historical assignments even when the active-company picker omits them.
    tail = replace(tail, "$user->clients()->sync($clientIds);", `$inactiveIds = $user->clients()->where('clients.is_active', false)->pluck('clients.id')->all();
        $user->clients()->sync(array_unique([...$clientIds, ...$inactiveIds]));`);
    tail = replace(tail, "$user->companyWorkDetails()->updateOrCreate(", "if ($workDetail) {\n        $user->companyWorkDetails()->updateOrCreate(");
    tail = tail.replace(/\$validated\['work_detail'\]/g, '$workDetail');
    // The replacement above also touches the initial validation branch, before $workDetail is set.
    tail = replace(tail, "$clientIds = $validated['client_ids'];", "$clientIds = $validated['client_ids'];\n        $workDetail = $validated['work_detail'] ?? null;");
    tail = replace(tail, "$workDetail = $workDetail ?? null;\n        unset", "unset");
    tail = replace(tail, "        return back();", "        }\n\n        return back();");
    return head + tail;
});
for (const path of ['app/Http/Controllers/Admin/Settings/UserSettingsController.php', 'app/Http/Controllers/Admin/Settings/EmployeeSettingsController.php']) {
    edit(path, text => text.replace(/SendAccountAccessLink::dispatch\(\$user->id\);/g, 'SendAccountAccessLink::dispatch($user->id)->afterCommit();'));
}
edit('app/Http/Controllers/Settings/ProfileController.php', text => replace(text,
    "$selectedCompanyId = $request->integer('client_id') ?: $companies->first()?->id;",
    "$requestedCompanyId = $request->integer('client_id');\n        $selectedCompanyId = $companies->contains('id', $requestedCompanyId)\n            ? $requestedCompanyId\n            : $companies->first()?->id;"));
edit('app/Http/Requests/Settings/ProfileUpdateRequest.php', text => {
    text = replace(text, "'client_id' => [\n                'required'", "'client_id' => [\n                'nullable'");
    return text.replace(/('(job_role|travel_allowance|travel_allowance_currency|salary|start_shift|end_shift)' => \[)/g, "$1'exclude_if:client_id,null', ");
});
