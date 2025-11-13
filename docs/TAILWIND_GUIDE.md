# دليل استخدام Tailwind CSS في مشروع مستشفى الأمل

## نظرة عامة

تم تطبيق Tailwind CSS على المشروع لتحسين التطوير وجعل التصميم أكثر احترافية وسهولة في الصيانة.

## الملفات الجديدة

### 1. `assets/css/tailwind.css`

الملف الرئيسي لـ Tailwind CSS مع الألوان المخصصة للمستشفى.

### 2. `index_tailwind.php`

الصفحة الرئيسية باستخدام Tailwind CSS.

### 3. `login_tailwind.php`

صفحة تسجيل الدخول باستخدام Tailwind CSS.

### 4. `register_tailwind.php`

صفحة التسجيل باستخدام Tailwind CSS.

## الألوان المخصصة

### لوحة ألوان المستشفى

```css
--primary-color: #2563eb; /* الأزرق الطبي */
--secondary-color: #22c55e; /* الأخضر العلاجي */
--accent-color: #ff914d; /* البرتقالي الإنساني */
--info-color: #38bdf8; /* الأزرق السماوي */
--bg-primary: #ffffff; /* الأبيض النقي */
--bg-secondary: #f1f5f9; /* الرمادي الهادئ */
--text-primary: #334155; /* الرمادي الداكن */
--text-secondary: #64748b; /* رمادي ثانوي */
--border-color: #e2e8f0; /* حدود ناعمة */
--success: #22c55e; /* نجاح */
--warning: #ff914d; /* تحذير */
--error: #ef4444; /* خطأ */
```

### استخدام الألوان في Tailwind

```html
<!-- الأزرق الطبي -->
<div class="text-hospital-primary">نص أزرق</div>
<div class="bg-hospital-primary">خلفية زرقاء</div>

<!-- الأخضر العلاجي -->
<div class="text-hospital-secondary">نص أخضر</div>
<div class="bg-hospital-secondary">خلفية خضراء</div>

<!-- البرتقالي الإنساني -->
<div class="text-hospital-accent">نص برتقالي</div>
<div class="bg-hospital-accent">خلفية برتقالية</div>
```

## المكونات المخصصة

### الأزرار

```html
<!-- زر أساسي -->
<button class="hospital-btn-primary">زر أساسي</button>

<!-- زر نجاح -->
<button class="hospital-btn-success">زر نجاح</button>

<!-- زر تفاعلي -->
<button class="hospital-btn-accent">زر تفاعلي</button>
```

### البطاقات

```html
<!-- بطاقة عادية -->
<div class="hospital-card">
  <h3>عنوان البطاقة</h3>
  <p>محتوى البطاقة</p>
</div>
```

### الأيقونات

```html
<!-- أيقونة دائرية -->
<div class="hospital-icon">
  <i class="fas fa-heartbeat"></i>
</div>
```

### العناوين

```html
<!-- عنوان رئيسي -->
<h1 class="hospital-text-primary">عنوان رئيسي</h1>

<!-- عنوان ثانوي -->
<p class="hospital-text-secondary">نص ثانوي</p>
```

## التخطيط والتصميم

### التخطيط المتجاوب

```html
<!-- شبكة متجاوبة -->
<div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
  <div class="hospital-card">عنصر 1</div>
  <div class="hospital-card">عنصر 2</div>
  <div class="hospital-card">عنصر 3</div>
</div>
```

### المسافات والهوامش

```html
<!-- مسافات متناسقة -->
<div class="p-8">padding 8</div>
<div class="m-4">margin 4</div>
<div class="space-y-6">مسافة بين العناصر</div>
```

### الظلال والحدود

```html
<!-- ظلال مخصصة -->
<div class="shadow-hospital">ظل عادي</div>
<div class="shadow-hospital-hover">ظل عند التمرير</div>

<!-- حدود مخصصة -->
<div class="border border-gray-200 rounded-hospital">حدود دائرية</div>
```

## التطبيقات العملية

### نموذج تسجيل الدخول

```html
<form class="space-y-6">
  <div>
    <label class="block text-sm font-semibold text-gray-700 mb-2"
      >البريد الإلكتروني</label
    >
    <input
      type="email"
      class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-hospital focus:ring-2 focus:ring-hospital-primary focus:border-transparent transition-all"
    />
  </div>
  <button type="submit" class="hospital-btn-primary w-full">
    تسجيل الدخول
  </button>
</form>
```

### قائمة التنقل

```html
<nav class="hospital-nav">
  <div class="container mx-auto px-4 py-4">
    <div class="flex items-center justify-between">
      <a href="#" class="text-2xl font-bold text-hospital-primary"
        >مستشفى الأمل</a
      >
      <div class="hidden md:flex items-center space-x-8 space-x-reverse">
        <a
          href="#"
          class="text-gray-700 hover:text-hospital-primary transition-colors"
          >الرئيسية</a
        >
        <a
          href="#"
          class="text-gray-700 hover:text-hospital-primary transition-colors"
          >المستشفيات</a
        >
      </div>
    </div>
  </div>
</nav>
```

### رسائل التنبيه

```html
<!-- رسالة خطأ -->
<div
  class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-hospital"
>
  <i class="fas fa-exclamation-circle"></i>
  <span>رسالة خطأ</span>
</div>

<!-- رسالة نجاح -->
<div
  class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-hospital"
>
  <i class="fas fa-check-circle"></i>
  <span>رسالة نجاح</span>
</div>
```

## أفضل الممارسات

### 1. استخدام المكونات المخصصة

```html
<!-- ✅ صحيح -->
<button class="hospital-btn-primary">زر</button>

<!-- ❌ خطأ -->
<button class="bg-blue-600 text-white px-4 py-2 rounded">زر</button>
```

### 2. التخطيط المتجاوب

```html
<!-- ✅ صحيح -->
<div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
  <!-- ❌ خطأ -->
  <div class="grid grid-cols-3 gap-8"></div>
</div>
```

### 3. الألوان المتناسقة

```html
<!-- ✅ صحيح -->
<div class="text-hospital-primary">نص</div>

<!-- ❌ خطأ -->
<div class="text-blue-600">نص</div>
```

### 4. المسافات المتناسقة

```html
<!-- ✅ صحيح -->
<div class="space-y-6">
  <div>عنصر 1</div>
  <div>عنصر 2</div>
</div>

<!-- ❌ خطأ -->
<div>
  <div class="mb-4">عنصر 1</div>
  <div class="mb-4">عنصر 2</div>
</div>
```

## التطوير المستقبلي

### إضافة مكونات جديدة

```css
/* إضافة مكون جديد */
.hospital-alert {
  @apply bg-yellow-50 border border-yellow-200 text-yellow-700 px-4 py-3 rounded-hospital;
}

.hospital-modal {
  @apply fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50;
}
```

### تخصيص الألوان

```css
/* إضافة لون جديد */
tailwind.config = {
  theme: {
    extend: {
      colors: {
        'hospital': {
          'new-color':'#FF6B6B', ;
        }
      }
    }
  }
}
```

## النصائح والتوصيات

1. **استخدم المكونات المخصصة** بدلاً من كتابة الأكواد من الصفر
2. **حافظ على التناسق** في استخدام الألوان والمسافات
3. **اختبر التجاوب** على جميع أحجام الشاشات
4. **استخدم الأيقونات** من Font Awesome لتحسين المظهر
5. **حافظ على البساطة** في التصميم لسهولة الاستخدام

## الدعم والمساعدة

إذا واجهت أي مشاكل أو تحتاج مساعدة في استخدام Tailwind CSS، يمكنك:

1. مراجعة هذا الدليل
2. زيارة موقع Tailwind CSS الرسمي
3. مراجعة أمثلة الكود في الملفات الجديدة
4. التواصل مع فريق التطوير
