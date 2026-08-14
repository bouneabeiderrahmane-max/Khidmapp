import '../../../core/models/localized_text.dart';

class Category {
  const Category({required this.id, required this.name, required this.slug, this.parentId});

  factory Category.fromJson(Map<String, dynamic> json) => Category(
    id: json['id'] as int,
    name: LocalizedText.fromJson(json['name'] as Map<String, dynamic>),
    slug: json['slug'] as String,
    parentId: json['parent_id'] as int?,
  );

  final int id;
  final LocalizedText name;
  final String slug;
  final int? parentId;
}

class ProductSummary {
  const ProductSummary({
    required this.id,
    required this.name,
    this.image,
    required this.boutiqueName,
    this.categoryName,
    required this.priceFromMru,
  });

  factory ProductSummary.fromJson(Map<String, dynamic> json) => ProductSummary(
    id: json['id'] as int,
    name: LocalizedText.fromJson(json['name'] as Map<String, dynamic>),
    image: json['image'] as String?,
    boutiqueName: (json['boutique'] as Map<String, dynamic>)['name'] as String,
    categoryName: json['category'] == null
        ? null
        : LocalizedText.fromJson((json['category'] as Map<String, dynamic>)['name'] as Map<String, dynamic>),
    priceFromMru: (json['price_from_mru'] as num).toDouble(),
  );

  final int id;
  final LocalizedText name;
  final String? image;
  final String boutiqueName;
  final LocalizedText? categoryName;
  final double priceFromMru;
}

class ProductVariantOption {
  const ProductVariantOption({
    required this.id,
    this.size,
    this.color,
    required this.inStock,
    required this.priceMru,
  });

  factory ProductVariantOption.fromJson(Map<String, dynamic> json) => ProductVariantOption(
    id: json['id'] as int,
    size: json['size'] as String?,
    color: json['color'] as String?,
    inStock: json['in_stock'] as bool,
    priceMru: (json['price_mru'] as num).toDouble(),
  );

  final int id;
  final String? size;
  final String? color;
  final bool inStock;
  final double priceMru;

  String get label => [size, color].whereType<String>().join(' · ');
}

class DeliveryEstimate {
  const DeliveryEstimate({required this.min, required this.max});

  factory DeliveryEstimate.fromJson(Map<String, dynamic> json) =>
      DeliveryEstimate(min: json['min'] as int, max: json['max'] as int);

  final int min;
  final int max;
}

class ProductDetail {
  const ProductDetail({
    required this.id,
    required this.name,
    this.description,
    this.images,
    required this.boutiqueName,
    required this.variants,
    this.deliveryEstimate,
  });

  factory ProductDetail.fromJson(Map<String, dynamic> json) => ProductDetail(
    id: json['id'] as int,
    name: LocalizedText.fromJson(json['name'] as Map<String, dynamic>),
    description: json['description'] as String?,
    images: (json['images'] as List?)?.map((e) => e.toString()).toList(),
    boutiqueName: (json['boutique'] as Map<String, dynamic>)['name'] as String,
    variants: (json['variants'] as List)
        .map((v) => ProductVariantOption.fromJson(v as Map<String, dynamic>))
        .toList(),
    deliveryEstimate: json['delivery_estimate_days'] == null
        ? null
        : DeliveryEstimate.fromJson(json['delivery_estimate_days'] as Map<String, dynamic>),
  );

  final int id;
  final LocalizedText name;
  final String? description;
  final List<String>? images;
  final String boutiqueName;
  final List<ProductVariantOption> variants;
  final DeliveryEstimate? deliveryEstimate;
}

class CatalogFilters {
  const CatalogFilters({this.query, this.categorySlug, this.sort, this.page = 1});

  final String? query;
  final String? categorySlug;
  final String? sort;
  final int page;

  CatalogFilters copyWith({
    String? query,
    bool clearQuery = false,
    String? categorySlug,
    bool clearCategory = false,
    String? sort,
    int? page,
  }) {
    return CatalogFilters(
      query: clearQuery ? null : (query ?? this.query),
      categorySlug: clearCategory ? null : (categorySlug ?? this.categorySlug),
      sort: sort ?? this.sort,
      page: page ?? this.page,
    );
  }

  Map<String, dynamic> toQueryParams() => {
    if (query != null && query!.isNotEmpty) 'q': query,
    if (categorySlug != null) 'category': categorySlug,
    if (sort != null) 'sort': sort,
    'page': page,
  };
}
