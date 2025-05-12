<script setup>
import { ref, reactive, onMounted, nextTick} from 'vue';
import axios from 'axios';
import { Head } from '@inertiajs/vue3';
import Main from '@/Layouts/Main.vue';
import toastr from 'toastr';
import 'toastr/build/toastr.min.css';

const props = defineProps({
    documents: Array,
});

let modalInstance = null;
const isEdit = ref(false);
const documentForm = reactive({
  id: null,
  section_name: '',
  icon_class: '',
  description: '',
  items: [],
});

const validationErrors = ref({});
let dataTableInstance;

const addArticle = () => {
    nextTick(() => {
    initializeSummernote();
    });
  documentForm.items.push({
    article_name: '',
    description: '',
  });
};

const removeArticle = (index) => {
  const el = document.getElementById(`article-desc-${index}`);
  if (el) {
    $(el).summernote('destroy');
  }
  documentForm.items.splice(index, 1);
  nextTick(() => {
    initializeSummernote(); // Reinitialize others
  });
};

const initializeSummernote = () => {
  nextTick(() => {
    if ($('.summernote').length) {
      $('.summernote').each(function () {
        const el = $(this);
        const index = el.data('index');

        const options = {
          placeholder: 'Article Content',
          height: 300,
          toolbar: [
            ['style', ['bold', 'italic', 'underline', 'clear']],
            ['font', ['strikethrough', 'superscript', 'subscript']],
            ['fontname', ['fontname']],
            ['fontsize', ['fontsize']],
            ['color', ['color']],
            ['para', ['ul', 'ol', 'paragraph', 'lineheight']],
            ['insert', ['link', 'picture', 'video', 'codeSnippet']], // 👈 Add custom button
            ['view', ['fullscreen', 'codeview', 'help']],
            ['table', ['table']],
            ['height', ['height']],
          ],
          fontNames: ['Arial', 'Arial Black', 'Comic Sans MS', 'Courier New', 'Tw Cen MT', 'Khmer OS Content'],
          fontNamesIgnoreCheck: ['Tw Cen MT', 'Khmer OS Content'],
          buttons: {
            codeSnippet: function (context) {
              let ui = $.summernote.ui;
              return ui.button({
                contents: '<i class="note-icon-code"/> Code',
                tooltip: 'Insert Code Snippet',
                click: function () {
                  context.invoke('editor.pasteHTML', '<pre><code>\nYour code here\n</code></pre>');
                }
              }).render();
            }
          },
          callbacks: {
            onChange: function (contents) {
              if (typeof index === 'undefined') {
                documentForm.description = contents;
              } else {
                documentForm.items[index].description = contents;
              }
            }
          }
        };

        if (typeof index !== 'undefined') {
          options.callbacks.onImageUpload = function (files) {
            uploadArticleImage(files[0], index, el);
          };
        }

        el.summernote(options);

        if (typeof index === 'undefined') {
          el.summernote('code', documentForm.description);
        } else {
          el.summernote('code', documentForm.items[index].description || '');
        }
      });
    }
  });
};


const openCreateModal = () => {
  resetForm();
  if (modalInstance) {
    modalInstance.show();
    nextTick(() => {
      initializeSummernote();
    });
  }
};

const uploadArticleImage = async (file, index, editor) => {
  const formData = new FormData();
  formData.append('image', file);

  try {
    const response = await axios.post('/upload-article-image', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });

    const { file_name, file_path, url } = response.data;

    editor.summernote('insertImage', url);

    // Save image info to the corresponding article item
    if (typeof index !== 'undefined') {
      documentForm.items[index].file_name = file_name;
      documentForm.items[index].file_path = file_path;
    }
  } catch (error) {
    console.error('Image upload failed:', error);
    toastr.error('Image upload failed');
  }
};


const openEditModal = async (rowData) => {
  try {
    // Set isEdit to true when editing
    isEdit.value = true;

    // Fetch the existing data for editing
    const response = await axios.get(`/documents/${rowData.id}/edit`);

    // Populate the form with the fetched data
    const documentData = response.data.document;

    // Assuming documentForm is reactive
    Object.assign(documentForm, {
      id: documentData.id,
      section_name: documentData.section_name,
      icon_class: documentData.icon_class,
      description: documentData.description,
      items: documentData.items.map(item => ({
        id: item.id,
        article_name: item.article_name,
        description: item.description,
        file_name: item.file_name,
        file_path: item.file_path,
      })),
    });

    // Open the modal and initialize Summernote for each item
    if (modalInstance) {
      modalInstance.show();
    }

    nextTick(() => {
      initializeSummernote(); // Initialize Summernote editor
    });

  } catch (error) {
    console.error('Failed to fetch document data:', error);
    toastr.error('Failed to load Document data. Please try again.', 'Error');
  }
};

const deleteDocument = async (id) => {
  try {
    const response = await axios.delete(`/documents/${id}`);
    if (response.status === 200) {
      toastr.success('Document deleted successfully');
      
      // Ensure the DataTable is refreshed properly
      dataTableInstance.clear(); // Clear current table data
      dataTableInstance.rows.add(props.documents); // Re-add the updated data
      dataTableInstance.draw(); // Re-render the table with updated data
    }
  } catch (error) {
    console.error('Error deleting document:', error);
    toastr.error('Failed to delete Document. Please try again.', 'Error');
  }
};


const resetForm = () => {
  isEdit.value = false;
  Object.assign(documentForm, {
    id: null,
    section_name: '',
    icon_class: '',
    description: '',
    items: [],
  });
  validationErrors.value = {};
  $('.summernote').summernote('code', ''); // Clear the editor content
};

const submitForm = async () => {
  try {
    validationErrors.value = {};

    // Upload base64 images for each article
    for (let i = 0; i < documentForm.items.length; i++) {
      const item = documentForm.items[i];

      const parser = new DOMParser();
      const doc = parser.parseFromString(item.description, 'text/html');
      const images = doc.querySelectorAll('img');

      for (const img of images) {
        if (img.src.startsWith('data:')) {
          const formData = new FormData();
          formData.append('image', dataURItoBlob(img.src));

          const res = await axios.post('/upload-article-image', formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
          });

          const { url, file_name, file_path } = res.data;

          // Replace base64 image with uploaded image URL
          img.src = url;
          item.file_name = file_name;
          item.file_path = file_path;
        }
      }

      // Save updated HTML (with image URLs)
      item.description = doc.body.innerHTML;
    }

    // Check if we're in edit mode or creating a new document
    const requestMethod = isEdit.value ? 'put' : 'post';
    const requestUrl = isEdit.value ? `/documents/${documentForm.id}` : '/documents';

    // Send the data to the server for saving or updating
    await axios[requestMethod](requestUrl, documentForm);

    toastr.success(isEdit.value ? 'Document updated successfully' : 'Document created successfully');

    // Close modal and reset form after submission
    if (modalInstance) {
      modalInstance.hide();
    }

    resetForm();
  } catch (error) {
    if (error.response && error.response.status === 422) {
      // Handle validation errors
      validationErrors.value = error.response.data.errors;
    } else {
      console.error('Form submission error:', error);
      toastr.error('An unexpected error occurred.', 'Error');
    }
  }
};



function dataURItoBlob(dataURI) {
  const byteString = atob(dataURI.split(',')[1]);
  const mimeString = dataURI.split(',')[0].split(':')[1].split(';')[0];
  const ab = new ArrayBuffer(byteString.length);
  const ia = new Uint8Array(ab);
  for (let i = 0; i < byteString.length; i++) {
    ia[i] = byteString.charCodeAt(i);
  }
  return new Blob([ab], { type: mimeString });
}


onMounted(() => {
initializeSummernote(); // Initialize Summernote editor
  nextTick(() => {
    const table = $('#document-table');
    if (table.length) {
      console.log('Initializing DataTable');
      dataTableInstance = table.DataTable({
        responsive: true,
        autoWidth: true,
        pageLength: 15,
        lengthMenu: [15, 25, 50, 100],
        data: props.documents,
        columns: [
          { data: null, render: (data, type, row, meta) => meta.row + 1 }, // Row number
          { data: 'section_name' }, // Cancellation number
          { data: 'description' },
          {
            data: null,
            render: () => `
              <div class="btn-group">
                <a href="#" class="btn btn-default btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                  <i class="fas fa-cog fa-fw"></i> <i class="fa fa-caret-down"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                  <li><a class="dropdown-item btn-show text-primary"><i class="fas fa-eye"></i> View</a></li>
                  <li><a class="dropdown-item btn-edit"><i class="fas fa-edit"></i> Edit</a></li>
                  <li><a class="dropdown-item btn-delete text-danger"><i class="fas fa-trash-alt"></i> Delete</a></li>
                </ul>
              </div>
            `,
          },
        ],
      });

      // Attach event listeners to the main table
      $('#document-table')
        .on('click', '.btn-show', function () {
          const rowData = dataTableInstance.row($(this).closest('tr')).data();
          if (rowData) {
            window.location.href = `/documents/${rowData.id}`;
          }
        })
        .on('click', '.btn-edit', function () {
          const rowData = dataTableInstance.row($(this).closest('tr')).data();
          if (rowData) {
            openEditModal(rowData);
          }
        })
        .on('click', '.btn-delete', function () {
          const rowData = dataTableInstance.row($(this).closest('tr')).data();
          if (rowData) {
            deleteDocument(rowData.id);
          }
        });
      // Handle actions inside child rows (responsive details)
      $('#document-table').on('click', '.dtr-details .btn-show', function () {
        const tr = $(this).closest('tr').prev(); // Get the parent row of the child
        const rowData = dataTableInstance.row(tr).data();
        if (rowData) {
          window.location.href = `/documents/${rowData.id}`;
        }
      });

      $('#document-table').on('click', '.dtr-details .btn-edit', function () {
        const tr = $(this).closest('tr').prev(); // Get the parent row of the child
        const rowData = dataTableInstance.row(tr).data();
        if (rowData) {
          openEditModal(rowData);
        }
      });

      $('#document-table').on('click', '.dtr-details .btn-delete', function () {
        const tr = $(this).closest('tr').prev(); // Get the parent row of the child
        const rowData = dataTableInstance.row(tr).data();
        if (rowData) {
          deleteDocumennt(rowData.id);
        }
      });

        const documentModalElement = document.getElementById('documentModal');
        if (documentModalElement) {
            modalInstance = new bootstrap.Modal(documentModalElement);
        }
    }
  });
});

</script>

<template>
    <Head title="Documents" />
    <Main>
        <div class="panel panel-inverse">
      <div class="panel-heading">
        <h4 class="panel-title">Product List</h4>
        <div class="panel-heading-btn">
          <a href="javascript:;" class="btn btn-xs btn-icon btn-default" data-toggle="panel-expand"><i class="fa fa-expand"></i></a>
          <a href="javascript:;" class="btn btn-xs btn-icon btn-success" data-toggle="panel-reload"><i class="fa fa-redo"></i></a>
          <a href="javascript:;" class="btn btn-xs btn-icon btn-warning" data-toggle="panel-collapse"><i class="fa fa-minus"></i></a>
          <a href="javascript:;" class="btn btn-xs btn-icon btn-danger" data-toggle="panel-remove"><i class="fa fa-times"></i></a>
        </div>
      </div>
      <div class="panel-body">
        <button @click="openCreateModal" class="btn btn-primary mb-4 btn-sm">Add Document</button>

        <!-- Product Table -->
        <table id="document-table" class="table table-bordered align-middle text-nowrap" width="100%">
          <thead>
            <tr>
              <th>#</th>
              <th>Section Name</th>
              <th>Description</th>
              <th>Actions</th>
            </tr>
          </thead>
        </table>
        </div>
        </div>

        <!-- Modal -->
        <div class="modal fade" id="documentModal" tabindex="-1" aria-labelledby="documentModalLabel" aria-hidden="true" ref="modalRef">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ isEdit ? 'Edit Document' : 'New Document' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <!-- Section Fields -->
                    <div class="mb-3">
                    <label class="form-label">Section Name</label>
                    <input type="text" class="form-control" v-model="documentForm.section_name" />
                    <div class="text-danger" v-if="validationErrors.section_name">{{ validationErrors.section_name[0] }}</div>
                    </div>

                    <div class="mb-3">
                    <label class="form-label">Icon Class</label>
                    <input type="text" class="form-control" v-model="documentForm.icon_class" />
                    <div class="text-danger" v-if="validationErrors.icon_class">{{ validationErrors.icon_class[0] }}</div>
                    </div>

                    <div class="mb-3">
                    <label class="form-label">Section Description</label>
                    <textarea class="form-control summernote" v-model="documentForm.description"></textarea>
                    <div class="text-danger" v-if="validationErrors.description">{{ validationErrors.description[0] }}</div>
                    </div>

                    <!-- Articles Section -->
                    <hr />
                    <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="mb-0">Articles</h5>
                    <button class="btn btn-sm btn-success" @click="addArticle">
                        <i class="fas fa-plus"></i> Add Article
                    </button>
                    </div>

                    <div v-for="(item, index) in documentForm.items" :key="index" class="border rounded p-3 mb-2">
                        <div class="mb-2">
                            <label class="form-label">Article Name</label>
                            <input type="text" class="form-control" v-model="item.article_name" />
                        </div>

                        <div class="mb-2">
                            <label class="form-label">Article Description</label>
                            <textarea
                            class="form-control summernote"
                            :id="`article-desc-${index}`"
                            :data-index="index"
                            ></textarea>
                        </div>

                        <button class="btn btn-sm btn-danger" @click="removeArticle(index)">
                            <i class="fas fa-trash-alt"></i> Remove Article
                        </button>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="resetForm" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" @click="submitForm">Save</button>
                </div>
                </div>
            </div>
        </div>

    </Main>
</template>

<style scoped>
  .note-editable {
    font-family: 'Noto Sans Khmer', 'Khmer OS Content', 'Tw Cen MT', sans-serif;
  }
</style>