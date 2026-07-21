<template>
    <div class="flex flex-col gap-5">
        <!-- Filter by Price -->
        <section class="border-b border-gray-100 pb-5 space-y-4">

            <!-- Clear & Apply -->
        <div class="flex gap-4 pt-2">
            <button
                class="grow px-3 py-1 rounded-[10px] border border-primary text-primary text-base font-medium leading-normal"
                @click="$emit('clear')"
            >
                {{ $t("Clear") }}
            </button>
            <button
                class="grow px-3 py-1 bg-primary rounded-[10px] border border-primary text-white text-base font-medium leading-normal"
                @click="$emit('apply')"
            >
                {{ $t("Apply") }}
            </button>
        </div>
            <button
                type="button"
                class="w-full flex justify-between items-center"
                @click="toggleSection('price')"
            >
                <span class="text-zinc-900 text-base font-medium leading-relaxed">
                    {{ $t("Filter by Price") }}
                </span>
                <ChevronDownIcon
                    class="w-5 h-5 text-gray-800 transition-transform duration-300"
                    :class="openSections.price ? 'rotate-180' : ''"
                />
            </button>

            <div v-show="openSections.price" class="space-y-4">
                <div class="w-[98%]">
                    <vue-slider
                        v-model="priceRange"
                        :min="filter.min_price"
                        :max="filter.max_price"
                        :rail-style="railStyle"
                        :process-style="processStyle"
                        :dot-style="dotStyle"
                    ></vue-slider>
                </div>

                <div class="flex items-center gap-2">
                    <p class="text-slate-500 text-sm font-normal leading-normal flex-shrink-0">
                        {{ $t("Price") }}:
                    </p>
                    <div class="flex items-center gap-1 flex-1 min-w-0">
                        <input
                            :value="priceRange[0]"
                            @input="priceRange = [$event.target.valueAsNumber, priceRange[1]]"
                            type="number"
                            class="h-9 flex-1 min-w-0 px-3 bg-neutral-100 rounded text-zinc-900 text-sm outline outline-1 outline-offset-[-1px] outline-gray-300"
                        />
                        <span class="text-gray-700 px-1">-</span>
                        <input
                            :value="priceRange[1]"
                            @input="priceRange = [priceRange[0], $event.target.valueAsNumber]"
                            type="number"
                            class="h-9 flex-1 min-w-0 px-3 bg-neutral-100 rounded text-zinc-900 text-sm outline outline-1 outline-offset-[-1px] outline-gray-300"
                        />
                    </div>
                </div>
            </div>
        </section>

        <!-- Sort by -->
        <section class="border-b border-gray-100 pb-5 space-y-3">
            <button
                type="button"
                class="w-full flex justify-between items-center"
                @click="toggleSection('sort')"
            >
                <span class="text-zinc-900 text-base font-medium leading-relaxed">
                    {{ $t("Sort by") }}
                </span>
                <ChevronDownIcon
                    class="w-5 h-5 text-gray-800 transition-transform duration-300"
                    :class="openSections.sort ? 'rotate-180' : ''"
                />
            </button>

            <select
                v-show="openSections.sort"
                v-model="form.sort_type"
                class="w-full p-3 rounded bg-transparent border border-gray-200 outline-none focus:border-primary"
            >
                <option v-for="sort in sortOptions" :key="sort.value" :value="sort.value">
                    {{ $t(sort.name) }}
                </option>
            </select>
        </section>

        <!-- Category (+ Subcategory) -->
        <section class="border-b border-gray-100 pb-5">
            <button
                type="button"
                class="w-full flex justify-between items-center mb-3"
                @click="toggleSection('category')"
            >
                <span class="text-zinc-900 text-base font-medium leading-relaxed">
                    {{ $t("Categories") }}
                </span>
                <ChevronDownIcon
                    class="w-5 h-5 text-gray-800 transition-transform duration-300"
                    :class="openSections.category ? 'rotate-180' : ''"
                />
            </button>

            <div v-show="openSections.category">
                <p
                    v-if="categories.length == 0"
                    class="text-error text-sm font-medium leading-normal capitalize"
                >
                    {{ $t("no categories available") }}
                </p>

                <div class="flex flex-col gap-2">
                    <template v-for="category in visibleCategories" :key="category.id">
                        <div class="flex items-center gap-2.5">
                            <label
                                :for="`category${category.id}`"
                                class="cursor-pointer flex items-center gap-2.5 text-slate-800 grow"
                            >
                                <input
                                    type="checkbox"
                                    :id="`category${category.id}`"
                                    :value="category.id"
                                    v-model="form.category_id"
                                    @change="onCategoryToggle"
                                    class="w-4 h-4 appearance-none checked:bg-primary rounded border-2 border-gray-400 shrink-0 transition duration-300"
                                />
                                <span class="text-base font-normal leading-normal">
                                    {{ $t(category.name) }}
                                </span>
                            </label>

                            <!-- Subcategory dropdown toggle -->
                            <button
                                v-if="category.sub_categories && category.sub_categories.length"
                                type="button"
                                class="shrink-0"
                                @click="toggleCategoryExpand(category.id)"
                            >
                                <ChevronDownIcon
                                    class="w-5 h-5 text-gray-500 transition-transform duration-300"
                                    :class="expandedCategories.includes(category.id) ? 'rotate-180' : ''"
                                />
                            </button>
                        </div>

                        <!-- Subcategories -->
                        <div
                            v-if="
                                expandedCategories.includes(category.id) &&
                                category.sub_categories &&
                                category.sub_categories.length
                            "
                            class="ml-6 flex flex-col gap-2 border-l border-gray-100 pl-3 py-1"
                        >
                            <label
                                v-for="sub in category.sub_categories"
                                :key="sub.id"
                                :for="`sub${sub.id}`"
                                class="cursor-pointer flex items-center gap-2.5 text-slate-600"
                            >
                                <input
                                    type="checkbox"
                                    :id="`sub${sub.id}`"
                                    :value="sub.id"
                                    v-model="form.sub_category_id"
                                    class="w-4 h-4 appearance-none checked:bg-primary rounded border-2 border-gray-400 shrink-0 transition duration-300"
                                />
                                <span class="text-sm font-normal leading-normal">
                                    {{ $t(sub.name) }}
                                </span>
                            </label>
                        </div>
                    </template>
                </div>

                <button
                    v-if="categories.length > defaultVisible"
                    type="button"
                    class="mt-3 text-primary text-sm font-medium leading-normal"
                    @click="showAllCategories = !showAllCategories"
                >
                    {{ showAllCategories ? $t("View Less") : $t("View More") }}
                </button>
            </div>
        </section>

        <!-- Category Attributes (loaded when a category is selected) -->
        <section
            v-for="attribute in attributes"
            :key="attribute.id"
            class="border-b border-gray-100 pb-5"
        >
            <div class="flex items-center gap-2.5 mb-3">
                <input
                    type="checkbox"
                    :id="`attrMain${attribute.id}`"
                    :value="attribute.id"
                    v-model="form.attribute_id"
                    class="w-4 h-4 appearance-none checked:bg-primary rounded border-2 border-gray-400 shrink-0 transition duration-300"
                />
                <button
                    type="button"
                    class="grow flex justify-between items-center"
                    @click="toggleAttribute(attribute.id)"
                >
                    <span class="text-zinc-900 text-base font-medium leading-relaxed">
                        {{ $t(attribute.name) }}
                    </span>
                    <ChevronDownIcon
                        class="w-5 h-5 text-gray-800 transition-transform duration-300"
                        :class="isAttributeOpen(attribute.id) ? 'rotate-180' : ''"
                    />
                </button>
            </div>

            <div v-show="isAttributeOpen(attribute.id)">
                <p
                    v-if="!attribute.sub_attributes || attribute.sub_attributes.length == 0"
                    class="text-error text-sm font-medium leading-normal capitalize"
                >
                    {{ $t("no options available") }}
                </p>

                <div class="flex flex-col gap-2 max-h-60 overflow-y-auto pr-1">
                    <label
                        v-for="sub in attribute.sub_attributes"
                        :key="sub.id"
                        :for="`attr${sub.id}`"
                        class="cursor-pointer flex items-center gap-2.5 text-slate-800"
                    >
                        <input
                            type="checkbox"
                            :id="`attr${sub.id}`"
                            :value="sub.id"
                            v-model="form.attribute_id"
                            class="w-4 h-4 appearance-none checked:bg-primary rounded border-2 border-gray-400 shrink-0 transition duration-300"
                        />
                        <span class="text-base font-normal leading-normal">
                            {{ $t(sub.name) }}
                        </span>
                    </label>
                </div>
            </div>
        </section>

        <!-- Brand -->
        <section class="border-b border-gray-100 pb-5">
            <button
                type="button"
                class="w-full flex justify-between items-center mb-3"
                @click="toggleSection('brand')"
            >
                <span class="text-zinc-900 text-base font-medium leading-relaxed">
                    {{ $t("Brands") }}
                </span>
                <ChevronDownIcon
                    class="w-5 h-5 text-gray-800 transition-transform duration-300"
                    :class="openSections.brand ? 'rotate-180' : ''"
                />
            </button>

            <div v-show="openSections.brand">
                <p
                    v-if="!filter.brands || filter.brands.length == 0"
                    class="text-error text-sm font-medium leading-normal capitalize"
                >
                    {{ $t("no brands available") }}
                </p>

                <div class="flex flex-col gap-2">
                    <label
                        v-for="brand in visibleBrands"
                        :key="brand.id"
                        :for="`brand${brand.id}`"
                        class="cursor-pointer flex items-center gap-2.5 text-slate-800"
                    >
                        <input
                            type="radio"
                            :id="`brand${brand.id}`"
                            name="brand"
                            :value="brand.id"
                            v-model="form.brand_id"
                            class="w-4 h-4 appearance-none checked:bg-primary rounded-full border-2 border-gray-400 shrink-0 transition duration-300"
                        />
                        <span class="text-base font-normal leading-normal">
                            {{ $t(brand.name) }}
                        </span>
                    </label>
                </div>

                <button
                    v-if="filter.brands && filter.brands.length > defaultVisible"
                    type="button"
                    class="mt-3 text-primary text-sm font-medium leading-normal"
                    @click="showAllBrands = !showAllBrands"
                >
                    {{ showAllBrands ? $t("View Less") : $t("View More") }}
                </button>
            </div>
        </section>

        <!-- Customer Review -->
        <section class="pb-1">
            <button
                type="button"
                class="w-full flex justify-between items-center mb-3"
                @click="toggleSection('review')"
            >
                <span class="text-zinc-900 text-lg font-medium leading-relaxed">
                    {{ $t("Customer Review") }}
                </span>
                <ChevronDownIcon
                    class="w-5 h-5 text-gray-800 transition-transform duration-300"
                    :class="openSections.review ? 'rotate-180' : ''"
                />
            </button>

            <div v-show="openSections.review" class="flex flex-col gap-2">
                <label
                    v-for="rating in ratings"
                    :key="rating"
                    :for="`rating${rating}`"
                    class="cursor-pointer flex items-center gap-2.5 text-slate-800"
                >
                    <input
                        type="radio"
                        :id="`rating${rating}`"
                        name="rating"
                        :value="rating"
                        v-model="form.rating"
                        class="w-4 h-4 appearance-none checked:bg-primary rounded-full border-2 border-gray-400 shrink-0 transition duration-300"
                    />
                    <span class="flex items-center gap-1">
                        <StarIcon
                            v-for="i in 5"
                            :key="i"
                            class="w-4 h-4"
                            :class="i <= rating ? 'text-amber-500' : 'text-gray-200'"
                        />
                        <span class="text-slate-800 text-sm font-normal leading-normal ml-1">
                            {{ rating }}.0 {{ $t("& Up") }}
                        </span>
                    </span>
                </label>
            </div>
        </section>

    </div>
</template>

<script setup>
import { ref, reactive, computed, watch } from "vue";
import VueSlider from "vue-slider-component";
import "vue-slider-component/theme/default.css";
import { StarIcon } from "@heroicons/vue/24/solid";
import { ChevronDownIcon } from "@heroicons/vue/20/solid";

const props = defineProps({
    filter: { type: Object, required: true },
    categories: { type: Array, default: () => [] },
    attributes: { type: Array, default: () => [] },
});

const form = defineModel({ type: Object, required: true });
const priceRange = defineModel("priceRange", { type: Array, required: true });

const emit = defineEmits(["apply", "clear", "categoryChange"]);

const ratings = [5, 4, 3, 2, 1];
const defaultVisible = 5;

const sortOptions = [
    { name: "Default Sorting", value: "default" },
    { name: "High to Low", value: "high_to_low" },
    { name: "Low to High", value: "low_to_high" },
    { name: "Most Selling", value: "top_selling" },
    { name: "New Product", value: "newest" },
];

// collapsible section state
const openSections = reactive({
    price: true,
    sort: true,
    category: true,
    brand: true,
    review: true,
});
const toggleSection = (key) => {
    openSections[key] = !openSections[key];
};

// attribute sections open state (default open)
const closedAttributes = ref([]);
const isAttributeOpen = (id) => !closedAttributes.value.includes(id);
const toggleAttribute = (id) => {
    const i = closedAttributes.value.indexOf(id);
    if (i > -1) closedAttributes.value.splice(i, 1);
    else closedAttributes.value.push(id);
};

// subcategory dropdown state
const expandedCategories = ref([]);
const toggleCategoryExpand = (id) => {
    const i = expandedCategories.value.indexOf(id);
    if (i > -1) expandedCategories.value.splice(i, 1);
    else expandedCategories.value.push(id);
};

// view more / less
const showAllCategories = ref(false);
const showAllBrands = ref(false);

const visibleCategories = computed(() =>
    showAllCategories.value
        ? props.categories
        : props.categories.slice(0, defaultVisible)
);
const visibleBrands = computed(() => {
    const brands = props.filter.brands || [];
    return showAllBrands.value ? brands : brands.slice(0, defaultVisible);
});

const onCategoryToggle = () => {
    emit("categoryChange", form.value.category_id);
};

// When a category / subcategory is preselected (e.g. arriving from the navbar),
// auto-expand its parent so the checked option is visible, and reveal the full
// list if that option sits beyond the first few (View More) items.
watch(
    () => [props.categories, form.value.category_id, form.value.sub_category_id],
    () => {
        props.categories.forEach((category) => {
            const hasSelectedSub = (category.sub_categories || []).some((sub) =>
                form.value.sub_category_id.includes(sub.id)
            );
            if (hasSelectedSub && !expandedCategories.value.includes(category.id)) {
                expandedCategories.value.push(category.id);
            }
        });

        const selectedIndex = props.categories.findIndex(
            (category) =>
                form.value.category_id.includes(category.id) ||
                (category.sub_categories || []).some((sub) =>
                    form.value.sub_category_id.includes(sub.id)
                )
        );
        if (selectedIndex >= defaultVisible) {
            showAllCategories.value = true;
        }
    },
    { immediate: true, deep: true }
);

// range slider styles
const railStyle = { height: "4px" };
const processStyle = { backgroundColor: "var(--primary)" };
const dotStyle = {
    borderColor: "var(--primary)",
    backgroundColor: "var(--primary)",
    borderRadius: "0px",
    width: "8px",
};
</script>
