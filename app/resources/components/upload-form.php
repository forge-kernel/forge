<div class="lg:col-span-2 space-y-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h2 class="text-lg font-semibold text-gray-900">File Upload</h2>
            <p class="text-sm text-gray-500 mt-1">Test file upload functionality</p>
        </div>
        <div class="p-6">
            <?= form_open('/__upload', 'POST', ['enctype' => 'multipart/form-data', 'class' => 'space-y-4']) ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Select File</label>
                <?= upload_input('file', 'avatars', ['csrf' => false, 'class' => 'block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-gray-900 file:text-white hover:file:bg-gray-800']) ?>
            </div>
            <button type="submit"
                    class="w-full sm:w-auto px-6 py-2.5 bg-gray-900 text-white font-medium rounded-lg hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900 transition-colors">
                Upload File
            </button>
            <?= form_close() ?>
        </div>
    </div>
</div>