<template>
  <div class="form-designer">
    <div class="designer-header">
      <h3>表单设计器 - {{ formTitle }}</h3>
      <div class="actions">
        <el-button type="primary" size="small" @click="saveForm">保存</el-button>
        <el-button size="small" @click="previewForm">预览</el-button>
      </div>
    </div>

    <div class="designer-container">
      <!-- 左侧组件面板 -->
      <div class="components-panel">
        <div class="panel-title">组件列表</div>
        <draggable
          :list="availableFields"
          :group="{ name: 'fields', pull: 'clone', put: false }"
          :sort="false"
          item-key="type"
          @end="onDragEnd"
        >
          <template #item="{ element }">
            <div class="component-item">
              <el-icon :size="20">
                <component :is="getFieldIcon(element.type)" />
              </el-icon>
              <span>{{ getFieldLabel(element.type) }}</span>
            </div>
          </template>
        </draggable>
      </div>

      <!-- 中间设计区域 -->
      <div class="design-area">
        <div class="form-preview">
          <el-form
            ref="previewForm"
            label-width="100px"
            :model="formData"
            size="medium"
          >
            <draggable
              :list="formFields"
              group="fields"
              handle=".drag-handle"
              @change="onFieldChange"
            >
              <template #item="{ element, index }">
                <div class="form-field" :class="{ active: activeIndex === index }">
                  <div class="field-header" @click="setActiveField(index)">
                    <el-icon class="drag-handle"><rank /></el-icon>
                    <span>{{ element.label }}</span>
                    <el-icon class="delete-field" @click.stop="removeField(index)">
                      <delete />
                    </el-icon>
                  </div>
                  <div class="field-content">
                    <component
                      :is="getFieldComponent(element.type)"
                      v-model="formData[element.name]"
                      :field="element"
                    />
                  </div>
                </div>
              </template>
            </draggable>
          </el-form>
        </div>
      </div>

      <!-- 右侧属性面板 -->
      <div class="properties-panel">
        <div class="panel-title">字段属性</div>
        <el-form
          v-if="activeField"
          label-width="80px"
          size="small"
        >
          <el-form-item label="字段标签">
            <el-input v-model="activeField.label" />
          </el-form-item>
          <el-form-item label="字段名称">
            <el-input v-model="activeField.name" :disabled="isEditMode" />
          </el-form-item>
          <el-form-item label="字段类型">
            <el-select v-model="activeField.type" disabled>
              <el-option
                v-for="(label, type) in fieldTypes"
                :key="type"
                :label="label"
                :value="type"
              />
            </el-select>
          </el-form-item>
          <el-form-item label="是否必填">
            <el-switch v-model="activeField.required" />
          </el-form-item>
          <el-form-item label="占位文本">
            <el-input v-model="activeField.placeholder" />
          </el-form-item>
          <el-form-item label="默认值">
            <el-input v-model="activeField.default_value" />
          </el-form-item>
          
          <!-- 选项字段的额外配置 -->
          <template v-if="hasOptions(activeField.type)">
            <el-divider>选项配置</el-divider>
            <div
              v-for="(option, idx) in activeField.options"
              :key="idx"
              class="option-item"
            >
              <el-input v-model="option.label" placeholder="显示文本" />
              <el-input v-model="option.value" placeholder="值" />
              <el-button
                type="danger"
                circle
                size="small"
                @click="removeOption(activeField, idx)"
              >
                <el-icon><delete /></el-icon>
              </el-button>
            </div>
            <el-button
              type="primary"
              size="small"
              @click="addOption(activeField)"
            >
              添加选项
            </el-button>
          </template>
        </el-form>
        <div v-else class="empty-tip">
          请选择字段进行配置
        </div>
      </div>
    </div>

    <!-- 预览对话框 -->
    <el-dialog
      v-model="previewVisible"
      title="表单预览"
      width="50%"
    >
      <FormPreview
        :fields="formFields"
        :form-data="formData"
      />
      <template #footer>
        <el-button @click="previewVisible = false">关闭</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import draggable from 'vuedraggable'
import {
  Document,
  Edit,
  Checked,
  List,
  Calendar,
  Upload,
  Rank,
  Delete
} from '@element-plus/icons-vue'

// 字段类型映射
const fieldTypes = {
  text: '单行文本',
  textarea: '多行文本',
  radio: '单选',
  checkbox: '多选',
  select: '下拉选择',
  date: '日期',
  file: '文件上传'
}

// 字段图标映射
const fieldIcons = {
  text: Edit,
  textarea: Document,
  radio: Checked,
  checkbox: List,
  select: List,
  date: Calendar,
  file: Upload
}

const props = defineProps({
  formId: {
    type: String,
    required: true
  },
  formTitle: {
    type: String,
    default: '未命名表单'
  },
  initialFields: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['save'])

// 表单数据
const formFields = ref([...props.initialFields])
const formData = reactive({})
const activeIndex = ref(-1)
const previewVisible = ref(false)

// 计算属性
const activeField = computed(() => {
  return activeIndex.value >= 0 ? formFields.value[activeIndex.value] : null
})

const isEditMode = computed(() => {
  return activeIndex.value >= 0 && formFields.value[activeIndex.value]?.name
})

const availableFields = computed(() => {
  return Object.keys(fieldTypes).map(type => ({
    type,
    name: '',
    label: fieldTypes[type],
    required: false,
    placeholder: '',
    default_value: '',
    ...(hasOptions(type) ? { options: [] } : {})
  }))
})

// 方法
const getFieldLabel = (type) => fieldTypes[type] || type
const getFieldIcon = (type) => fieldIcons[type] || Document
const hasOptions = (type) => ['radio', 'checkbox', 'select'].includes(type)

const getFieldComponent = (type) => {
  switch (type) {
    case 'text': return 'el-input'
    case 'textarea': return 'el-input'
    case 'radio': return 'el-radio-group'
    case 'checkbox': return 'el-checkbox-group'
    case 'select': return 'el-select'
    case 'date': return 'el-date-picker'
    case 'file': return 'el-upload'
    default: return 'el-input'
  }
}

const setActiveField = (index) => {
  activeIndex.value = index
}

const removeField = (index) => {
  formFields.value.splice(index, 1)
  if (activeIndex.value === index) {
    activeIndex.value = -1
  } else if (activeIndex.value > index) {
    activeIndex.value--
  }
}

const onDragEnd = (evt) => {
  if (evt.to.className.includes('design-area')) {
    const newField = {
      ...evt.item._underlying_vm_,
      name: `field_${Date.now()}`,
      label: `${fieldTypes[evt.item._underlying_vm_.type]} ${formFields.value.length + 1}`
    }
    formFields.value.push(newField)
    activeIndex.value = formFields.value.length - 1
  }
}

const onFieldChange = () => {
  // 字段顺序变化处理
}

const addOption = (field) => {
  if (!field.options) {
    field.options = []
  }
  field.options.push({
    label: '',
    value: ''
  })
}

const removeOption = (field, index) => {
  field.options.splice(index, 1)
}

const saveForm = () => {
  emit('save', {
    id: props.formId,
    fields: formFields.value
  })
}

const previewForm = () => {
  previewVisible.value = true
}
</script>

<style scoped>
.form-designer {
  height: 100%;
  display: flex;
  flex-direction: column;
}

.designer-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px;
  border-bottom: 1px solid #eee;
}

.designer-container {
  display: flex;
  flex: 1;
  overflow: hidden;
}

.components-panel {
  width: 200px;
  border-right: 1px solid #eee;
  padding: 10px;
  overflow-y: auto;
}

.design-area {
  flex: 1;
  padding: 20px;
  overflow-y: auto;
}

.properties-panel {
  width: 300px;
  border-left: 1px solid #eee;
  padding: 10px;
  overflow-y: auto;
}

.panel-title {
  font-weight: bold;
  margin-bottom: 10px;
  padding-bottom: 5px;
  border-bottom: 1px solid #eee;
}

.component-item {
  padding: 8px;
  margin-bottom: 5px;
  border: 1px dashed #ddd;
  cursor: move;
  display: flex;
  align-items: center;
}

.component-item:hover {
  background-color: #f5f7fa;
}

.component-item i {
  margin-right: 8px;
}

.form-preview {
  max-width: 800px;
  margin: 0 auto;
}

.form-field {
  margin-bottom: 20px;
  border: 1px solid #ddd;
  border-radius: 4px;
  padding: 10px;
  position: relative;
}

.form-field.active {
  border-color: #409eff;
}

.field-header {
  display: flex;
  align-items: center;
  margin-bottom: 10px;
  padding-bottom: 5px;
  border-bottom: 1px solid #eee;
  cursor: pointer;
}

.field-header .drag-handle {
  cursor: move;
  margin-right: 10px;
}

.field-header .delete-field {
  margin-left: auto;
  cursor: pointer;
  color: #f56c6c;
}

.field-content {
  padding: 0 10px;
}

.option-item {
  display: flex;
  align-items: center;
  margin-bottom: 10px;
}

.option-item .el-input {
  flex: 1;
}

.empty-tip {
  color: #999;
  text-align: center;
  padding: 20px;
}
</style>