import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:khidmapp/app.dart';

void main() {
  testWidgets('App builds and shows the catalog placeholder', (tester) async {
    await tester.pumpWidget(const ProviderScope(child: KhidmappApp()));
    await tester.pumpAndSettle();

    expect(find.text('Catalogue'), findsWidgets);
  });
}
