<template>
    <div>
        <!-- Top bar -->
        <!-- <div class="main-container py-4 bg-slate-100 mt-[25px]">
            <div
                v-if="!isLoading"
                class="w-full p-2 md:p-4 bg-white rounded-lg sm:rounded-xl md:rounded-2xl flex gap-3 md:gap-6 items-center justify-between"
            >
                <router-link
                    to="/"
                    class="py-2 flex gap-1 sm:gap-2 items-center justify-center"
                >
                    <ArrowLeftIcon class="w-4 h-4 sm:w-6 sm:h-6 text-slate-600" />
                    <div class="text-slate-600 text-sm sm:text-base font-normal leading-normal">
                        {{ $t("Back") }}
                    </div>
                </router-link>

                <div class="grow overflow-x-auto whitespace-nowrap">
                    <span class="text-primary">“{{ master.search || "all" }}”</span>
                    <span class="text-slate-800 text-base font-normal pl-2">
                        {{ totalProducts }} {{ $t("items found") }}
                    </span>
                </div>

                <div class="lg:hidden">
                    <button
                        class="p-2 sm:px-4 sm:py-3 bg-slate-200 rounded-md sm:rounded-[10px] justify-center items-center gap-2 inline-flex text-slate-600 text-sm sm:text-base font-normal leading-normal border-0 outline-none hover:text-primary transition duration-300"
                        @click="showFilterCanvas = true"
                    >
                        <FunnelIcon class="w-4 h-4 sm:w-6 sm:h-6" />
                        <div class="grow shrink basis-0">{{ $t("Filter") }}</div>
                    </button>
                </div>
            </div>

            <div
                v-else
                class="w-full p-2 md:p-4 bg-white rounded-lg sm:rounded-xl md:rounded-2xl flex gap-3 md:gap-6 items-center justify-between"
            >
                <SkeletonLoader
                    v-for="i in 2"
                    class="w-24 sm:w-32 md:w-72 lg:w-96 h-12 rounded"
                />
            </div>
        </div> -->

        <!-- Body: sidebar + products -->
        <div class="main-container py-8 lg:py-12">
            <div class="flex gap-6 items-start">
                <!-- Desktop filter sidebar -->
                <aside
                    class="hidden lg:block w-[280px] shrink-0 sticky top-4 bg-white rounded-2xl border border-slate-200 p-5 max-h-[calc(100vh-2rem)] overflow-y-auto filter-scroll"
                >
                    <div class="text-zinc-900 text-sm font-semibold leading-loose ">
                        {{ $t("Filter By") }}
                    </div>
                    <ProductFilterPanel
                        v-model="filterFormData"
                        v-model:priceRange="priceRange"
                        :filter="filter"
                        :categories="master.categories"
                        :attributes="attributes"
                        @apply="applyFilter"
                        @clear="clearFilter"
                        @category-change="onCategoryChange"
                    />
                </aside>

                <!-- Products -->
                <div class="grow min-w-0">
                    <!-- Title -->
                    <div class="flex items-center justify-between gap-4 mb-5">
                        <div class="flex items-baseline gap-2">
                            <h1 class="text-zinc-900 text-base sm:text-xl font-semibold leading-snug">
                                {{ $t("All Products") }}
                            </h1>
                        </div>

                        <button
                            class="lg:hidden p-2 sm:px-4 sm:py-2.5 bg-slate-100 rounded-lg justify-center items-center gap-2 inline-flex text-slate-600 text-sm sm:text-base font-normal leading-normal border-0 outline-none hover:text-primary transition duration-300"
                            @click="showFilterCanvas = true"
                        >
                            <FunnelIcon class="w-5 h-5" />
                            <span>{{ $t("Filter") }}</span>
                        </button>
                    </div>

                    <div
                        class="grid grid-cols-1 xs:grid-cols-2 sm:grid-cols-3 lg:grid-cols-3 xl:grid-cols-4 gap-3 sm:gap-6 items-start"
                    >
                        <div
                            v-if="!isLoading"
                            v-for="product in products"
                            :key="product.id"
                            class="w-full"
                        >
                            <ProductCardToggler :productData="product" />
                        </div>

                        <!-- loading -->
                        <div v-else v-for="i in 12" :key="i">
                            <SkeletonLoader class="w-full h-[220px] sm:h-[330px] rounded-lg" />
                        </div>
                    </div>

                    <div
                        v-if="products.length == 0 && !isLoading"
                        class="flex justify-center items-center w-full mt-8"
                    >
                        <div class="text-slate-800 text-base font-normal leading-normal">
                            {{ $t("No products found") }}
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div
                        v-if="products.length > 0 && !isLoading"
                        class="flex justify-between items-center w-full mt-8 gap-4 flex-wrap"
                    >
                        <div class="text-slate-800 text-base font-normal leading-normal">
                            {{ $t("Showing") }} {{ perPage * (currentPage - 1) + 1 }}
                            {{ $t("to") }}
                            {{ perPage * (currentPage - 1) + products.length }}
                            {{ $t("of") }} {{ totalProducts }} {{ $t("results") }}
                        </div>
                        <div>
                            <vue-awesome-paginate
                                :total-items="totalProducts"
                                :items-per-page="perPage"
                                type="button"
                                :max-pages-shown="maxPagesShown"
                                v-model="currentPage"
                                :hide-prev-next-when-ends="true"
                                @click="onClickHandler"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mobile Filter Drawer -->
        <TransitionRoot as="template" :show="showFilterCanvas">
            <Dialog as="div" class="relative z-50 lg:hidden" @close="showFilterCanvas = false">
                <TransitionChild
                    as="template"
                    enter="ease-in-out duration-500"
                    enter-from="opacity-0"
                    enter-to="opacity-100"
                    leave="ease-in-out duration-500"
                    leave-from="opacity-100"
                    leave-to="opacity-0"
                >
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-30 transition-opacity" />
                </TransitionChild>

                <div class="fixed inset-0 overflow-hidden">
                    <div class="absolute inset-0 overflow-hidden">
                        <div
                            class="pointer-events-none fixed inset-y-0 flex max-w-full"
                            :class="
                                master.langDirection == 'rtl'
                                    ? 'left-0 sm:pr-10'
                                    : 'right-0 sm:pl-10'
                            "
                        >
                            <TransitionChild
                                as="template"
                                enter="transform transition ease-in-out duration-500 sm:duration-700"
                                :enter-from="
                                    master.langDirection == 'rtl'
                                        ? '-translate-x-full'
                                        : 'translate-x-full'
                                "
                                enter-to="translate-x-0"
                                leave="transform transition ease-in-out duration-500 sm:duration-700"
                                leave-from="translate-x-0"
                                :leave-to="
                                    master.langDirection == 'rtl'
                                        ? '-translate-x-full'
                                        : 'translate-x-full'
                                "
                            >
                                <DialogPanel class="pointer-events-auto relative w-screen max-w-md">
                                    <div
                                        class="flex h-full flex-col overflow-y-auto scrollbar-hide bg-white shadow-xl"
                                    >
                                        <div
                                            class="flex justify-between items-center p-4 px-6 sticky top-0 z-10 bg-neutral-100"
                                        >
                                            <div class="text-zinc-900 text-2xl font-semibold leading-loose">
                                                {{ $t("Filter") }}
                                            </div>
                                            <button
                                                class="w-8 h-8 flex justify-center items-center bg-slate-100 rounded-full"
                                                @click="showFilterCanvas = false"
                                            >
                                                <XMarkIcon class="w-5 h-5 text-slate-700" />
                                            </button>
                                        </div>

                                        <div class="p-4 px-6">
                                            <ProductFilterPanel
                                                v-model="filterFormData"
                                                v-model:priceRange="priceRange"
                                                :filter="filter"
                                                :categories="master.categories"
                                                :attributes="attributes"
                                                @apply="applyFilter"
                                                @clear="clearFilter"
                                                @category-change="onCategoryChange"
                                            />
                                        </div>
                                    </div>
                                </DialogPanel>
                            </TransitionChild>
                        </div>
                    </div>
                </div>
            </Dialog>
        </TransitionRoot>
    </div>
</template>

<script setup>
import { onMounted, onBeforeUnmount, ref, watch, computed } from "vue";
import {
    Dialog,
    DialogPanel,
    TransitionChild,
    TransitionRoot,
} from "@headlessui/vue";
import { FunnelIcon, XMarkIcon, ArrowLeftIcon } from "@heroicons/vue/24/outline";
import ProductCardToggler from "../components/ProductCardToggler.vue";
import ProductFilterPanel from "../components/ProductFilterPanel.vue";
import SkeletonLoader from "../components/SkeletonLoader.vue";
import { useMaster } from "../stores/MasterStore";
import { useScroll } from "../composables/useScroll";

const master = useMaster();

const isLoading = ref(true);
const showFilterCanvas = ref(false);

const { viewportWidth } = useScroll();

const priceRange = ref([0, 1000]);

const filterFormData = ref({
    rating: null,
    sort_type: "default",
    brand_id: "",
    category_id: [],
    sub_category_id: [],
    attribute_id: [],
    min_price: null,
    max_price: null,
});

const filter = ref({
    brands: [],
    min_price: 0,
    max_price: 1000,
});

const attributes = ref([]);
const products = ref([]);
const totalProducts = ref(0);
const setRangeValue = ref(true);

const currentPage = ref(1);
const perPage = 12;

const maxPagesShown = computed(() => {
    if (viewportWidth.value <= 320) return 2;
    if (viewportWidth.value <= 780) return 3;
    return 5;
});

onMounted(async () => {
    setRangeValue.value = true;
    await ensureCategories();
    applyRouteSelection();
    fetchProducts();
    window.scrollTo(0, 0);
});

onBeforeUnmount(() => {
    setRangeValue.value = false;
    master.search = null;
});

watch(
    () => master.search,
    () => {
        fetchProducts();
    }
);

// A navbar category/subcategory click while already on /products keeps the URL
// unchanged, so react to the store handoff instead of the route.
watch(
    () => master.pendingCategory,
    async (pending) => {
        if (!pending) return;
        await ensureCategories();
        applyRouteSelection();
        currentPage.value = 1;
        fetchProducts();
    }
);

const onClickHandler = (page) => {
    currentPage.value = page;
    fetchProducts();
};

const ensureCategories = async () => {
    if (!master.categories || master.categories.length === 0) {
        const response = await axios.get("/categories");
        master.categories = response.data.data.categories;
    }
};

// Preselect the category / subcategory the user arrived from. A navbar click
// stashes the ids in master.pendingCategory (one-shot); the URL stays /products.
const applyRouteSelection = () => {
    // Products.vue is reused for both /products and /categories/:slug, so on
    // SPA navigation the component is NOT remounted. Reset the whole filter
    // form to defaults here, otherwise filters from the previous view (brand,
    // rating, attribute, price, sort) leak in and shrink the result set.
    filterFormData.value = {
        rating: null,
        sort_type: "default",
        brand_id: "",
        category_id: [],
        sub_category_id: [],
        attribute_id: [],
        min_price: null,
        max_price: null,
    };
    attributes.value = [];
    setRangeValue.value = true;

    const pending = master.pendingCategory;
    master.pendingCategory = null; // consume once; reload/clear shows full list
    if (!pending) return;

    // the most specific id the user actually clicked
    const clickedId = pending.subcategory || pending.category;
    if (!clickedId) return;

    const isTopLevel = master.categories.some((c) => c.id === clickedId);
    if (isTopLevel) {
        filterFormData.value.category_id = [clickedId];
    } else {
        filterFormData.value.sub_category_id = [clickedId];
    }

    // an attribute chip (from the Category page) preselects that attribute too
    if (pending.attribute) {
        filterFormData.value.attribute_id = [pending.attribute];
    }

    onCategoryChange([clickedId]);
};

const onCategoryChange = async (categoryIds) => {
    if (!categoryIds || categoryIds.length === 0) {
        attributes.value = [];
        return;
    }

    const responses = await Promise.all(
        categoryIds.map((id) =>
            axios.get("/category/" + id + "/attributes", {
                headers: { "Accept-Language": master.locale || "en" },
            })
        )
    );

    // merge attributes from every selected category, de-duplicated by id
    const merged = [];
    const seen = new Set();
    responses.forEach((response) => {
        response.data.data.attributes.forEach((attribute) => {
            if (!seen.has(attribute.id)) {
                seen.add(attribute.id);
                merged.push(attribute);
            }
        });
    });
    attributes.value = merged;
};

const fetchProducts = async () => {
    isLoading.value = true;
    window.scrollTo({ top: 0, behavior: "smooth" });

    // categories + subcategories filter on the same category_id column
    // (a subcategory is itself a category id the API can filter on).
    const effectiveCategory = [
        ...filterFormData.value.category_id,
        ...filterFormData.value.sub_category_id,
    ];

    axios
        .get("/products", {
            params: {
                page: currentPage.value,
                per_page: perPage,
                search: master.search,
                rating: filterFormData.value.rating,
                sort_type: filterFormData.value.sort_type,
                brand_id: filterFormData.value.brand_id,
                attribute_id: filterFormData.value.attribute_id,
                min_price: filterFormData.value.min_price,
                max_price: filterFormData.value.max_price,
                category_id: effectiveCategory.length ? effectiveCategory : null,
            },
            headers: {
                "Accept-Language": master.locale || "en",
                currency_id: master.selectedCurrency.id,
            },
        })
        .then((response) => {
            totalProducts.value = response.data.data.total;
            products.value = response.data.data.products;
            filter.value = response.data.data.filters;

            if (setRangeValue.value) {
                priceRange.value = [
                    Math.floor(filter.value.min_price),
                    Math.floor(filter.value.max_price),
                ];
            }

            setTimeout(() => {
                isLoading.value = false;
            }, 200);
        })
        .catch(() => {
            isLoading.value = false;
        });
};

const clearFilter = () => {
    filterFormData.value = {
        rating: null,
        sort_type: "default",
        brand_id: "",
        category_id: [],
        sub_category_id: [],
        attribute_id: [],
        min_price: filter.value.min_price,
        max_price: filter.value.max_price,
    };
    attributes.value = [];
    priceRange.value = [
        Math.floor(filter.value.min_price),
        Math.floor(filter.value.max_price),
    ];
    setRangeValue.value = true;
    currentPage.value = 1;
    fetchProducts();
};

const applyFilter = () => {
    filterFormData.value.min_price = priceRange.value[0];
    filterFormData.value.max_price = priceRange.value[1];

    setRangeValue.value = false;
    master.search = null;
    currentPage.value = 1;
    showFilterCanvas.value = false;
    fetchProducts();
};
</script>

<style>
.vue-slider-process {
    @apply bg-primary;
}

.scrollbar-hide::-webkit-scrollbar {
    display: none;
}

.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}

/* thin, subtle scrollbar for the filter sidebar */
.filter-scroll {
    scrollbar-width: thin;
    scrollbar-color: #cbd5e1 transparent;
}

.filter-scroll::-webkit-scrollbar {
    width: 6px;
}

.filter-scroll::-webkit-scrollbar-thumb {
    background-color: #cbd5e1;
    border-radius: 9999px;
}

.filter-scroll::-webkit-scrollbar-track {
    background: transparent;
}
</style>
